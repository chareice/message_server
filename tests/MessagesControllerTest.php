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
      'sender_id' => 1
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
      'sender_id' => 1
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
      'sender_id' => 1
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
      'sender_id' => 1
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
}
