<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Message extends Model{
  const USER_TARGET_TYPE = 'user';
  const GLOBALE_TARGET_TYPE = 'globale';
  const GROUP_TARGET_TYPE = 'group';

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
        collect($this->afterCreatedQueue)->each(function($task){
          $task->message_id = $this->id;
          $task->save();
        });
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

    $content = array_get($options, 'content', null);
    $targets = array_get($options, 'targets', null);
    $sender_id = array_get($options, 'sender_id', null);
    $target_type = array_get($options, 'target_type', null);

    $message->content = $content;
    $message->sender_id = $sender_id;

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

    $queue = collect($targets)->map(function($target){
      $message_target = new MessageTarget(['target_id' => $target]);
      return $message_target;
    });

    $this->setAfterCreatedQueue($queue);
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

  public static function getUnRead($user_id){
    // not using this otherwise will throw Segmentation fault
    // $commonSelect = Message::select('messages.id', 'messages.content', 'messages.created_at');
    $messageQuery = self::globalMessagesQuery($user_id)
                          ->union(self::userMessagesQuery($user_id))
                          ->union(self::groupMessagesQuery($user_id));

    $res = $messageQuery->get();
    return $res;
  }

  //全局消息
  public static function globalMessagesQuery($user_id){
    return Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->leftJoin('target_status', function($join) use ($user_id){
                              $join->on('messages.id', '=', 'target_status.message_id')
                                   ->where('target_status.target_id', '=', $user_id);
                            })->where('messages.target_type', '=', self::GLOBALE_TARGET_TYPE)
                              ->whereNull('target_status.message_id');
  }


  //用户消息
  public static function userMessagesQuery($user_id){
    return Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->join('message_targets', 'message_targets.message_id', '=', 'messages.id')
                            ->leftJoin('target_status', 'target_status.message_id', '=', 'messages.id')
                            ->where('message_targets.target_id', '=', $user_id)
                            ->whereNull('target_status.message_id');
  }

  //群组消息
  public static function groupMessagesQuery($user_id){
    $query = Message::select('messages.id', 'messages.content', 'messages.created_at', 'messages.sender_id')
                            ->join('group_message', 'group_message.message_id', '=', 'messages.id')
                            ->join('groups', 'group_message.group_id', '=', 'groups.id')
                            ->join('group_targets', 'groups.id', '=', 'group_targets.group_id')
                            ->leftJoin('target_status', function($join){
                              $join->on('messages.id', '=', 'target_status.message_id')
                                   ->on('target_status.target_id', '=', 'group_targets.target_id');
                            })->where('group_targets.target_id', '=', $user_id)
                              ->whereNull('target_status.message_id');
    return $query;
  }
}
