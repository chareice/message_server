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
    $target_type = array_get($options, 'target_type', null);

    $message->content = $content;

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

  //设置群组消息
  public function prepareGroupMessage($targets){
    $this->target_type = self::GROUP_TARGET_TYPE;
    $this->setAfterCreatedQueue($targets);
  }

  public static function getTargetTypeFromOptions(){

  }
}
