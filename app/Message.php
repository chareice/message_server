<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Message extends Model{
  const USER_TARGET_TYPE = 'user';
  const GLOBALE_TARGET_TYPE = 'globale';
  const GROUP_TARGET_TYPE = 'group';
  const DEFAULT_NAMESPACE = 'main';

  private $afterCreatedQueue = [];

  public static function boot(){
    parent::boot();

    static::created(function($model){
      $model->afterCreate();
    });
  }

  public function afterCreate(){
    DB::transaction(function(){
      if($this->target_type == self::USER_TARGET_TYPE){
        $insertQueue = [];
        foreach ($this->afterCreatedQueue as $key => $value) {
          $item = [];
          $item['message_id'] = $this->id;
          $item['target_id'] = $value;
          array_push($insertQueue, $item);
        }

        DB::table('message_targets')->insert($insertQueue);

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

  public static function buildWithOptions($options){
    $message = new self;
    $message_type = 'multcast';

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

      case self::GLOBALE_TARGET_TYPE:
        $message->prepareGlobaleMessage();
        break;

      case self::GROUP_TARGET_TYPE:
        $message->prepareGroupMessage($targets);
        break;

      default:
        # code...
        break;
    }

    return $message;
  }

  //设置全局消息
  public function prepareGlobaleMessage(){
    $this->target_type = self::GLOBALE_TARGET_TYPE;
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
    return self::readMessagesQuery($user_id, $namespace)->get();
  }

  public static function getUnReadQueryBuilder($user_id, $namespace=Message::DEFAULT_NAMESPACE){
    $queryBuilder = self::globalUnReadMessageQuery($user_id, $namespace)
                          ->union(self::userUnReadMessagesQuery($user_id, $namespace))
                          ->union(self::groupUnReadMessagesQuery($user_id, $namespace));
    return $queryBuilder;
  }

  //全局已读消息
  public static function readMessagesQuery($user_id, $namespace){
    $query = Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
              ->join('target_status', 'messages.id', '=', 'target_status.message_id')
              ->where('target_status.target_id', '=', $user_id)
              ->where('messages.namespace', '=', $namespace)
              ->where('target_status.status', '=', 'read');
    return $query;
  }

  //全局未读消息
  public static function globalUnReadMessageQuery($user_id, $namespace){
    return Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->leftJoin('target_status', function($join) use ($user_id){
                              $join->on('messages.id', '=', 'target_status.message_id')
                                   ->where('target_status.target_id', '=', $user_id);
                            })->where('messages.target_type', '=', self::GLOBALE_TARGET_TYPE)
                              ->where('messages.namespace', '=', $namespace)
                            ->whereNull('target_status.message_id');
  }

  //用户未读消息
  public static function userUnReadMessagesQuery($user_id, $namespace){
    return Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->join('message_targets', 'message_targets.message_id', '=', 'messages.id')
                            ->leftJoin('target_status', 'target_status.message_id', '=', 'messages.id')
                            ->where('message_targets.target_id', '=', $user_id)
                            ->where('messages.namespace', '=', $namespace)
                            ->whereNull('target_status.message_id');
  }

  //未读群组消息
  public static function groupUnReadMessagesQuery($user_id, $namespace){
    $query = Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->join('group_message', 'group_message.message_id', '=', 'messages.id')
                            ->join('groups', 'group_message.group_id', '=', 'groups.id')
                            ->join('group_targets', 'groups.id', '=', 'group_targets.group_id')
                            ->leftJoin('target_status', function($join){
                              $join->on('messages.id', '=', 'target_status.message_id')
                                   ->on('target_status.target_id', '=', 'group_targets.target_id');
                            })->where('group_targets.target_id', '=', $user_id)
                              ->where('messages.namespace', '=', $namespace)
                              ->whereNull('target_status.message_id');
    return $query;
  }
}
