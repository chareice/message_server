<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use DB;
use Log;

class Message extends Model{
  const USER_TARGET_TYPE = 'user';
  const global_TARGET_TYPE = 'global';
  const GROUP_TARGET_TYPE = 'group';
  const DEFAULT_NAMESPACE = 'main';

  private $afterCreatedQueue = [];

  protected $fillable = ['expiration_time', 'effective_time', 'content', 'title'];

  public static function boot(){
    parent::boot();

    static::created(function($model){
      $model->afterCreate();
    });
  }

  /* 增加分片插入
   * General error: 1390 Prepared statement contains too many placeholders
   * PlaceHolders的项目数不能超过65535 对于本应用意味着不能超过32767
   */
  public function afterCreate(){
    DB::transaction(function(){
      if($this->target_type == self::USER_TARGET_TYPE){
        foreach(array_chunk($this->afterCreatedQueue, 5000) as $chunk){
          $insertQueue = [];
          foreach ($chunk as $key => $value) {
            $item = [];
            $item['message_id'] = $this->id;
            $item['target_id'] = $value;
            array_push($insertQueue, $item);
          }
          DB::table('message_targets')->insert($insertQueue);
        }

      }else if($this->target_type == self::GROUP_TARGET_TYPE){
        $groups = Group::whereIn('id', $this->afterCreatedQueue)->get();
        $this->targets()->attach($groups);
      }
    });
  }

  public function setAfterCreatedQueue($queue){
    $this->afterCreatedQueue = $queue;
  }

  public function targets(){
    //用户
    if($this->target_type == self::USER_TARGET_TYPE){
      return $this->hasMany('App\MessageTarget');
    }else{
      //群组
      return $this->belongsToMany('App\Group');
    }
  }

  public function userTargets(){
    return $this->hasMany('App\MessageTarget');
  }

  public static function buildWithOptions($options){
    $message = new self;

    $auto_assign_attrs = [
        'content', 'title', 'namespace',
        'sender_id', 'effective_time', 'expiration_time'
    ];

    collect($auto_assign_attrs)->each(function($attr) use ($options, $message){
      $attr_value = array_get($options, $attr, null);
      if($attr_value){
        $message->$attr = $attr_value;
      }
    });

    $targets = array_get($options, 'targets', null);
    $target_type = array_get($options, 'target_type', null);

    switch ($target_type) {
      case self::USER_TARGET_TYPE:
        $message->prepareUserMessage($targets);
        break;

      case self::global_TARGET_TYPE:
        $message->prepareglobalMessage();
        break;

      case self::GROUP_TARGET_TYPE:
        $message->prepareGroupMessage($targets);
        break;

      default:
        throw new \Exception('invalid target');
        break;
    }

    return $message;
  }

  //设置全局消息
  public function prepareglobalMessage(){
    $this->target_type = self::global_TARGET_TYPE;
  }

  //设置用户消息
  public function prepareUserMessage($targets){
    $this->target_type = self::USER_TARGET_TYPE;

    $this->setAfterCreatedQueue($targets);
  }

  //用户阅读消息
  public function readBy($user_id){
    $status = new TargetStatus;
    $status->message_id = $this->id;
    $status->target_id = $user_id;
    $status->status = 'read';
    $status->save();
  }

  //设置群组消息
  public function prepareGroupMessage($targets){
    $this->target_type = self::GROUP_TARGET_TYPE;
    $this->setAfterCreatedQueue($targets);
  }

  //获取未读消息
  public static function getUnRead($user_id, $namespace='main'){
    $res = self::getUnReadQueryBuilder($user_id, $namespace)->get();
    return $res;
  }

  //获取未读消息数量
  public static function getUnReadCount($user_id, $namespace=Message::DEFAULT_NAMESPACE){
    $sub = self::getUnReadQueryBuilder($user_id, $namespace);
    $queryBuilder = DB::table(DB::raw("({$sub->toSql()}) as sub"))
        ->mergeBindings($sub->getQuery());
    $res = $queryBuilder->count();
    return $res;
  }

  //获取已读消息
  public static function getRead($user_id, $namespace=Message::DEFAULT_NAMESPACE){
    return self::readMessagesQueryWithReadAt($user_id, $namespace)->get();
  }

  public static function getUnReadQueryBuilder($user_id, $namespace=Message::DEFAULT_NAMESPACE){
    $queryBuilder = self::globalUnReadMessageQuery($user_id, $namespace)
        ->union(self::userUnReadMessagesQuery($user_id, $namespace));
    return $queryBuilder;
  }

  public static function readMessagesQueryWithReadAt($user_id, $namespace){
    $query = Message::select('messages.id', 'messages.title', 'messages.content',
        'messages.created_at', 'messages.sender_id', DB::raw("'read' as read_status"), 'target_status.created_at as read_at')
        ->join('target_status', 'messages.id', '=', 'target_status.message_id')
        ->where('target_status.target_id', '=', $user_id)
        ->where('messages.namespace', '=', $namespace)
        ->where('target_status.status', '=', 'read');
    return $query;
  }

  //全局已读消息
  public static function readMessagesQuery($user_id, $namespace){
    $query = Message::select('messages.id', 'messages.title', 'messages.content',
        'messages.created_at', 'messages.sender_id', DB::raw("'read' as read_status"))
        ->join('target_status', 'messages.id', '=', 'target_status.message_id')
        ->where('target_status.target_id', '=', $user_id)
        ->where('messages.namespace', '=', $namespace)
        ->where('target_status.status', '=', 'read');
    return $query;
  }

  //全局未读消息
  public static function globalUnReadMessageQuery($user_id, $namespace){
    $now = Carbon::now()->toDateTimeString();
    $queryBuilder = Message::select('messages.id', 'messages.title', 'messages.content', 'messages.created_at', 'messages.sender_id', DB::raw("'unread' as read_status"))
        ->leftJoin('target_status', function($join) use ($user_id){
          $join->on('messages.id', '=', 'target_status.message_id')
              ->where('target_status.target_id', '=', $user_id);
        })->where('messages.target_type', '=', self::global_TARGET_TYPE)
        ->where('messages.namespace', '=', $namespace)
        ->where(function($query) use ($now){
          $query->where('messages.effective_time', '=', null)
              ->orWhere('messages.effective_time', '<', $now);
        })->where(function($query) use ($now){
          $query->where('messages.expiration_time', '=', null)
              ->orWhere('messages.expiration_time', '>', $now);
        })
        ->whereNull('target_status.message_id');
    return $queryBuilder;
  }

  //用户未读消息
  public static function userUnReadMessagesQuery($user_id, $namespace){
    $now = Carbon::now()->toDateTimeString();
    $queryBuilder =  Message::select('messages.id', 'messages.title', 'messages.content', 'messages.created_at', 'messages.sender_id', DB::raw("'unread' as read_status"))
        ->join('message_targets', 'message_targets.message_id', '=', 'messages.id')
        ->leftJoin('target_status', 'target_status.message_id', '=', 'messages.id')
        ->where('message_targets.target_id', '=', $user_id)
        ->where('messages.namespace', '=', $namespace)
        ->where(function($query) use ($now){
          $query->where('messages.effective_time', '=', null)
              ->orWhere('messages.effective_time', '<', $now);
        })->where(function($query) use ($now){
          $query->where('messages.expiration_time', '=', null)
              ->orWhere('messages.expiration_time', '>', $now);
        })
        ->whereNull('target_status.message_id');
    return $queryBuilder;
  }

  //未读群组消息
  public static function groupUnReadMessagesQuery($user_id, $namespace){
    $now = Carbon::now()->toDateTimeString();
    $query = Message::select('messages.id', 'messages.title', 'messages.content', 'messages.created_at', 'messages.sender_id', DB::raw("'unread' as read_status"))
        ->join('group_message', 'group_message.message_id', '=', 'messages.id')
        ->join('groups', 'group_message.group_id', '=', 'groups.id')
        ->join('group_targets', 'groups.id', '=', 'group_targets.group_id')
        ->leftJoin('target_status', function($join){
          $join->on('messages.id', '=', 'target_status.message_id')
              ->on('target_status.target_id', '=', 'group_targets.target_id');
        })->where('group_targets.target_id', '=', $user_id)
        ->where('messages.namespace', '=', $namespace)
        ->where(function($query) use ($now){
          $query->where('messages.effective_time', '=', null)
              ->orWhere('messages.effective_time', '<', $now);
        })->where(function($query) use ($now){
          $query->where('messages.expiration_time', '=', null)
              ->orWhere('messages.expiration_time', '>', $now);
        })
        ->whereNull('target_status.message_id');
    return $query;
  }

  public static function mergedQuery($user_id, $namespace=Message::DEFAULT_NAMESPACE){
    $query = self::getUnReadQueryBuilder($user_id, $namespace)
        ->union(self::readMessagesQuery($user_id, $namespace));
    return $query;
  }
}
