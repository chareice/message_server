<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

use App\Group;
use App\Message;

class MessagesController extends Controller{

  public function index(Request $request){
    $per_page = 15;
    if($request->input('per_page')){
      $per_page = 1;
    }

    $current_page = 1;
    if($request->input('page')){
      $current_page = $request->input('page');
    }

    Paginator::currentPageResolver(function() use ($current_page) {
      return $current_page;
    });

    $messages = Message::orderBy('id', 'desc')->paginate($per_page);

    $messages_array = $messages->toArray();

    $data = $messages_array['data'];
    $meta = [
      'current_page' => $messages_array['current_page'],
      'total' => $messages_array['total'],
      'per_page' => $messages_array['per_page']
    ];
    return $this->responseJson($data, $meta);
  }

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

  //获取未读消息数量
  public function getUnReadMessageCount($user_id, Request $request){
    $unreadMessageCount = Message::getUnReadCount($user_id);
    return $this->responseJson($unreadMessageCount);
  }

  //阅读消息
  public function read(Request $request){
    $user_id = $request->input('user_id');
    $message_id = $request->input('message_id');
    $message = Message::find($message_id);
    $message->readBy($user_id);
    return $this->responseJson();
  }

  //获取已读消息
  public function getReadMessage($user_id){
    $readMessages = Message::getRead($user_id);
    return $this->responseJson($readMessages);
  }
}
