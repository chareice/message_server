<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Message;
use App\Group;

class MessageTest extends TestCase
{
  //测试新增一条消息
  public function testCreateMessage(){
    $message = new Message;
    $message->content = 'hello world!';
    $message->sender_id = 1;
    $message->target_type = 'multcast';

    $this->assertEquals(0, Message::count());
    $message->save();
    $this->assertEquals(1, Message::count());
  }

 /*
  *  发送消息接口
  *  参数
  * content: 消息内容
  * target_type: 目标类型(组／用户／群体)
  * targets: 目标
  */
  public function testCreateMessageForMultipleUser(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $this->assertEquals(1, Message::count());

    $message = Message::first();

    $this->assertEquals($options['target_type'], $message->target_type);
    $this->assertEquals(count($options['targets']), $message->targets->count());
  }

  //发送群组消息
  public function testCreateGroupMessage(){
    $message_content = 'this is a message';
    $groups = [];

    //生成10个Group
    for ($i=0; $i < 10; $i++) {
      $group = new Group;
      $group->name = $this->faker->name;
      $group->save();
      array_push($groups, $group);
    }

    $users = [];
    //生成100个用户
    for ($i=0; $i < 100; $i++) {
      array_push($users, $i);
    }

    collect($groups)->each(function($group) use ($users){
      //为每个组添加用户
      $group->addUsers(array_rand($users, 10));
    });

    //按组群发
    $options = [
      'content' => $message_content,
      'targets' => [1, 2],
      'target_type' => 'group'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $this->assertEquals(1, Message::count());

    $message = Message::first();
    $this->assertEquals($options['target_type'], $message->target_type);
    $this->assertEquals(count($options['targets']), $message->targets()->count());
  }

  //发送全局消息
  public function testCreateMessageForGlobaleUser(){
    $message_content = 'some message';

    $options = [
      'content' => $message_content,
      'target_type' => 'globale',
      'sender' => 1
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $this->assertEquals(1, Message::count());

    $message = Message::first();
    $this->assertEquals($options['target_type'], $message->target_type);
  }
}
