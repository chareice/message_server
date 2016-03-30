<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Group;
use App\Message;

class MessagesController extends Controller{
  //创建消息
  public function create(Request $request){
    $content = $request->input('content');
    $target_type = $request->input('target_type');
    $targets = $request->input('targets');
    $sender_id = $request->input('sender_id');

    $options = [
      'content' => $content,
      'target_type' => $target_type,
      'targets' => $targets,
      'sender_id' => $sender_id
    ];

    $message = Message::buildWithOptions($options);
    $message->save();
    return $this->responseJson();
  }

  //用户获取未读消息
  public function getUnReadMessage($user_id, Request $request){
    $unreadMessages = Message::getUnRead($user_id);
    return $this->responseJson($unreadMessages);
  }

  public function read(Request $request){
    $user_id = $request->input('user_id');
    $message_id = $request->input('message_id');
    $message = Message::find($message_id);
    $message->readBy($user_id);
    return $this->responseJson();
  }
}
