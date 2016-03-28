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
      collect($this->afterCreatedQueue)->each(function($task){
        $task->message_id = $this->id;
        $task->save();
      });
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
      return $this->hasMany('App\Group');
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

      default:
        # code...
        break;
    }

    return $message;
  }

  public function prepareGlobaleMessage(){
    $this->target_type = self::GLOBALE_TARGET_TYPE;
  }

  public function prepareUserMessage($targets){
    $this->target_type = self::USER_TARGET_TYPE;

    $queue = collect($targets)->map(function($target){
      $message_target = new MessageTarget(['target_id' => $target]);
      return $message_target;
    });

    $this->setAfterCreatedQueue($queue);
  }

  public static function getTargetTypeFromOptions(){

  }
}
