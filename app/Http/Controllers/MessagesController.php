<?php

namespace App\Http\Controllers;

use App\TargetStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

use DB;

use App\Group;
use App\Message;
class MessagesController extends Controller{
  const DEFAULT_NAMESPACE = 'main';
  # 获取所有消息
  public function index(Request $request){

    #设置当前取页数
    $per_page = 15;
    if($request->input('per_page')){
      $per_page = $request->input('per_page');
    }

    #设置当前页

    $current_page = $request->input('page', 1);

    Paginator::currentPageResolver(function() use ($current_page) {
      return $current_page;
    });

    $messages_query = Message::with('userTargets')->orderBy('id', 'desc');
    $messages_query->where('namespace', $request->input('namespace', self::DEFAULT_NAMESPACE))->orderBy('id', 'desc');

    $messages = $messages_query->paginate($per_page);

    $messages_array = $messages->toArray();
    $data = $messages_array['data'];

    $data = collect($data)->map(function($item){
      $targets = collect($item['user_targets'])->map(function($target){
        return $target['target_id'];
      });

      $item['user_targets'] = $targets;
      return $item;
    });

    $meta = [
      'current_page' => $messages_array['current_page'],
      'last_page' => $messages_array['last_page'],
      'total' => $messages_array['total'],
      'per_page' => $messages_array['per_page']
    ];
    return $this->responseJson($data, $meta);
  }

  //获取已读记录
  public function getReadLog(Request $request){
    $namespace = $request->input('namespace', self::DEFAULT_NAMESPACE);
    $per_page = $request->input('per_page', 20);
    $query = TargetStatus::join('messages', 'target_status.message_id', '=', 'messages.id')
            ->where('messages.namespace', '=', $namespace)
            ->orderBy('target_status.created_at', 'desc')
            ->select('messages.id as message_id',
                'messages.title as title',
                'target_status.created_at as read_at',
                'target_status.target_id'
                );
    $target_status = $query->paginate($per_page)->toArray();
    $data = $target_status['data'];
    $meta = [
        'current_page' => $target_status['current_page'],
        'last_page' => $target_status['last_page'],
        'total' => $target_status['total'],
        'per_page' => $target_status['per_page']
    ];

    return $this->responseJson($data, $meta);
  }

  //创建消息
  public function create(Request $request){
    $content = $request->input('content');
    $title = $request->input('title');
    $target_type = $request->input('target_type');
    $targets = $request->input('targets');
    $sender_id = $request->input('sender_id');
    $expiration_time = $request->input('expiration_time');
    $effective_time = $request->input('effective_time');
    $namespace = $request->input('namespace');

    $options = [
      'content' => $content,
      'target_type' => $target_type,
      'targets' => $targets,
      'sender_id' => $sender_id,
      'title' => $title,
      'expiration_time' => $expiration_time,
      'effective_time' => $effective_time,
      'namespace' => $namespace
    ];

    $message = Message::buildWithOptions($options);
    $message->save();
    return $this->responseJson();
  }

  //用户获取未读消息
  public function getUnReadMessage($user_id, Request $request){
    $namespace = $request->input('namespace', self::DEFAULT_NAMESPACE);
    $unreadMessageQuery = Message::getUnReadQueryBuilder($user_id, $namespace)->orderBy('id', 'desc');

    $messages = $unreadMessageQuery->get();

    $page = $request->input('page', 1);
    $paginate = $request->input('per_page', 20);

    $slice = $messages->slice($paginate * ($page - 1), $paginate);
    $messages = new LengthAwarePaginator($slice, count($messages), $paginate);

    $messages = $messages->toArray();

    $data = $messages['data'];
    $meta = [
        'current_page' => $messages['current_page'],
        'last_page' => $messages['last_page'],
        'total' => $messages['total'],
        'per_page' => $messages['per_page']
    ];

    return $this->responseJson($data, $meta);
  }

  //获取未读消息数量
  public function getUnReadMessageCount($user_id, Request $request){
    $namespace = $request->input('namespace', self::DEFAULT_NAMESPACE);
    $unreadMessageCount = Message::getUnReadCount($user_id, $namespace);
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
  public function getReadMessage($user_id, Request $request){
    $namespace = $request->input('namespace', self::DEFAULT_NAMESPACE);
    $readMessageQuery = Message::readMessagesQueryWithReadAt($user_id, $namespace)->orderBy('id', 'desc');
    $messages = $readMessageQuery->paginate()->toArray();

    $data = $messages['data'];
    $meta = [
        'current_page' => $messages['current_page'],
        'last_page' => $messages['last_page'],
        'total' => $messages['total'],
        'per_page' => $messages['per_page']
    ];
    return $this->responseJson($data, $meta);
  }

  //获取合并消息
  public function getMergedMessage($user_id, Request $request){
    $namespace = $request->input('namespace', self::DEFAULT_NAMESPACE);
    $mergedMessageQuery = Message::mergedQuery($user_id, $namespace);
    $mergedMessageQuery = $mergedMessageQuery->orderBy('id', 'desc');

    $per_page = $request->input('per_page', 20);
    $page = $request->input('page');
    $skip = ($page - 1) * $per_page;

    $countQuery = DB::table(DB::raw("({$mergedMessageQuery->toSql()}) as sub"))
        ->mergeBindings($mergedMessageQuery->getQuery());

    $count = $countQuery->count();

    $messages = $mergedMessageQuery->skip($skip)->take($per_page)->get();
    $data = $messages;
    $meta = [
        'current_page' => $page,
        'last_page' => ($count > $per_page) ? ceil($count / $per_page) : 1,
        'total' => $count,
        'per_page' => $per_page
    ];
    return $this->responseJson($data, $meta);
  }

  //获取消息内容
  public function show($message_id){
    $message = Message::with('userTargets')->find($message_id);
    $message = $message->toArray();
    $message['user_targets'] = collect($message['user_targets'])->map(function($target){
        return $target['target_id'];
    });
    return $this->responseJson($message);
  }

  //删除消息
  public function destroy($message_id){
    $message = Message::find($message_id);
    $message->delete();
    return $this->responseJson();
  }

  //修改消息
  public function update($message_id, Request $request){
    $message = Message::find($message_id);
    $message->update($request->all());
    return $this->responseJson();
  }
}
