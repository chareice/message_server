<?php

use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Group;
use App\Message;

class MessagesControllerTest extends TestCase{
  //发送全局消息测试
  public function testCreateMessageForGlobaleUser(){
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);
  }

  //发送带有效期的函数
  public function testCreateMessageForGlobaleUserWithExpirationTime(){
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title',
      'expiration_time'=> '2018-01-01'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);
    $message = Message::first();
    $this->assertEquals('2018-01-01 00:00:00', $message->expiration_time);
  }

  //获取消息测试
  public function testUserGetMessages(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    //获取消息
    $response = $this->call('GET', '/users/1/unread_messages');
    $this->assertResponseOk();
  }

  //用户阅读消息测试
  public function testReadMessage(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $this->assertEquals(1, count(Message::getUnRead(1)));
    $response = $this->call('POST', '/messages/read', ['user_id' => 1, 'message_id' => Message::first()->id]);
    $this->seeInDatabase('messages', $options);
    $this->assertEquals(0, count(Message::getUnRead(1)));
  }

  //获取用户未读消息数量
  public function testGetUnReadMessageCount(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    //获取消息
    $response = $this->call('GET', '/users/1/unread_messages_count');
    $this->assertResponseOk();
    $data = $response->getData();
    $this->assertEquals(1, $data->data);
  }

  //获取用户已读消息
  public function testGetReadMessages(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $message = Message::first();
    $response = $this->call('GET', '/users/1/read_messages');
    $data = $response->getData();
    $this->assertEquals(0, count($data->data));
    $message->readBy(1);
    $response = $this->call('GET', '/users/1/read_messages');
    $data = $response->getData();
    $this->assertEquals(1, count($data->data));
  }

  //获取系统消息列表
  public function testMessages(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $firstMessage = Message::first();
    $lastMessage = Message::orderBy('id', 'desc')->first();

    $this->assertNotEquals($firstMessage->id, $lastMessage->id);
    $response = $this->call('GET', '/messages?per_page=1');
    $this->assertResponseOk();
    $this->assertEquals(1, $response->getData()->meta->per_page);
    $this->assertEquals($lastMessage->id, $response->getData()->data[0]->id);

    $response = $this->call('GET', '/messages?per_page=1&page=2');
    $this->assertResponseOk();
    $this->assertEquals($firstMessage->id, $response->getData()->data[0]->id);
  }

  //获取系统消息列表带命名空间
  public function testGetMessagesWithNameSpace(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title',
      'namespace' => 'oem1'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title',
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $namespaceMessage = Message::orderBy('id', 'asc')->first();
    $mainMessage = Message::orderBy('id', 'desc')->first();

    $this->assertEquals('oem1', $namespaceMessage->namespace);
    $this->assertEquals('main', $mainMessage->namespace);

    $response = $this->call('GET', '/messages?namespace=oem1');
    $this->assertResponseOk();
    $this->assertEquals($namespaceMessage->id, $response->getData()->data[0]->id);

    //namespace用户获取未读消息
    $response = $this->call('GET', '/users/1/unread_messages_count?namespace=oem1');
    $this->assertResponseOk();
    $this->assertEquals(1, $response->getData()->data);
  }

  //删除消息
  public function testDeleteMessage(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title',
      'namespace' => 'oem1'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $message = Message::first();

    $this->call('DELETE', '/messages/'.$message->id);
    $this->assertEquals(0, Message::count());
  }

  //获取消息信息
  public function testGetMessageContent(){
    //创建一条全局消息
    $options = [
      'content' =>'Some Content',
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title',
      'namespace' => 'oem1'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $message = Message::first();
    $response = $this->call('GET', '/messages/'.$message->id);
    $this->assertEquals($message->id, $response->getData()->data->id);

    # 获取主命名空间消息
    $response = $this->call('GET', '/messages/');
    $this->assertEquals(0, count($response->getData()->data));
    # 获取oem1命名空间消息
    $response = $this->call('GET', '/messages?namespace=oem1');
    $this->assertEquals(1, count($response->getData()->data));

    # 用户获取oem1命名空间消息
    $response = $this->call('GET', '/users/1/unread_messages_count?namespace=oem1');
    $res = $response->getData()->data;
    $this->assertEquals(1, $res);

    # 获取主命名空间消息
    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(0, $res);
  }

  public function testTimeBoundMessage(){
    //创建一条没有设置实效的消息 能够获取到该消息
    $options = [
        'content' =>'Some Content',
        'target_type' => 'globale',
        'sender_id' => 1,
        'title' => 'this is title'
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(1, $res);
  }

  public function testTimeBoundWithEffectiveTimeFutureMessage(){
    //创建一条明天才生效的消息 不能获取到该消息
    $options = [
        'content' =>'Some Content',
        'target_type' => 'globale',
        'sender_id' => 1,
        'title' => 'this is title',
        'effective_time' => Carbon::now()->addDay()->toDateTimeString()
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(0, $res);
  }

  public function testTimeBoundWithEffectiveTimeAgoMessage(){
    //创建一条昨天已经生效的消息 能获取到该消息
    $options = [
        'content' =>'Some Content',
        'target_type' => 'globale',
        'sender_id' => 1,
        'title' => 'this is title',
        'effective_time' => Carbon::now()->subDay()->toDateTimeString()
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(1, $res);
  }

  public function testTimeBoundWithExpirationTimeAgoMessage(){
    //创建一条昨天过期的消息 不能获取到该消息
    $options = [
        'content' =>'Some Content',
        'target_type' => 'globale',
        'sender_id' => 1,
        'title' => 'this is title',
        'expiration_time' => Carbon::now()->subDay()->toDateTimeString()
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(0, $res);
  }

  public function testTimeBoundWithExpirationTimeFutureMessage(){
    //创建一条明天过期的消息 能获取到该消息
    $options = [
        'content' =>'Some Content',
        'target_type' => 'globale',
        'sender_id' => 1,
        'title' => 'this is title',
        'expiration_time' => Carbon::now()->addDay()->toDateTimeString()
    ];

    $this->post('/messages', $options);
    $this->assertResponseOk();
    $this->seeInDatabase('messages', $options);

    $response = $this->call('GET', '/users/1/unread_messages_count');
    $res = $response->getData()->data;
    $this->assertEquals(1, $res);
  }
}
