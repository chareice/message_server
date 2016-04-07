<?php

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
}
