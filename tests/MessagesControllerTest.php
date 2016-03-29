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
    $this->get('/users/1/unread_messages');
    // $this->assertResponseOk();
  }
}
