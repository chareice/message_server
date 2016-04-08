<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Message;
use App\Group;
use Carbon\Carbon;

class MessageTest extends TestCase
{
  //测试新增一条消息
  public function testCreateMessage(){
    $message = new Message;
    $message->content = 'hello world!';
    $message->title = 'this is my title';
    $message->sender_id = 1;
    $message->target_type = 'multcast';

    $this->assertEquals(0, Message::count());
    $message->save();
    $this->assertEquals(1, Message::count());
  }

  //新增消息 有失效时间
  public function testCreateMessageWithEffectiveTime(){
    $message_content = 'some message content';

    $effective_time = Carbon::now()->addDay();
    $expiration_time = Carbon::now()->addWeek();
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3, "some user_id"],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title',
      'effective_time' => $effective_time,
      'expiration_time' => $expiration_time
    ];

    $message = Message::buildWithOptions($options);
    $message->save();
    $message = Message::first();
    $this->assertEquals($effective_time->toDateTimeString(), $message->effective_time);
    $this->assertEquals($expiration_time->toDateTimeString(), $message->expiration_time);
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
      'targets' => [1, 2, 3, "some user_id"],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
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
      'target_type' => 'group',
      'sender_id' => 1,
      'title' => 'this is title'
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
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $this->assertEquals(1, Message::count());

    $message = Message::first();
    $this->assertEquals($options['target_type'], $message->target_type);
  }

  //获取全局未读消息
  public function testGetGlobaleUnReadMessage(){
    $message_content = 'some message';

    //写入全局消息
    $options = [
      'content' => $message_content,
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $unreadMessages = Message::getUnRead(1);
    $this->assertEquals(1, count($unreadMessages));
  }

  public function testGetUserUnReadMessage(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $this->assertEquals(1, Message::count());

    $message = Message::first();

    $unreadMessages = Message::getUnRead(1);
    $this->assertEquals(1, count($unreadMessages));
  }

  //测试获取组群发消息
  public function testGetGroupUnReadMessage(){
    $message_content = 'this is a message';
    $group = new Group;
    $group->name = $this->faker->name;
    $group->save();

    $group2 = new Group;
    $group2->name = $this->faker->name;
    $group2->save();

    $group->addUsers([1, 2, 3]);
    $group2->addUsers([2, 3]);

    //按组群发
    $options = [
      'content' => $message_content,
      'targets' => [1, 2],
      'target_type' => 'group',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $options = [
      'content' => $message_content,
      'targets' => [2],
      'target_type' => 'group',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    //用户1能看到1消息
    $unreadMessages = Message::getUnRead(1);
    $this->assertEquals(1, count($unreadMessages));

    //用户2能看到2消息
    $unreadMessages = Message::getUnRead(2);
    $this->assertEquals(2, count($unreadMessages));
  }

  //测试获取混合消息记录
  public function testGetUnReadMessage(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    $message_content = 'some message';

    $options = [
      'content' => $message_content,
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $message = Message::buildWithOptions($options);
    $message->save();

    //用户2可以看到两条消息
    $this->assertEquals(2, count(Message::getUnRead(1)));
    //用户4只可以看到1条消息
    $this->assertEquals(1, count(Message::getUnRead(4)));
  }

  //测试读取消息
  public function testReadMessage(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $userMessage = Message::buildWithOptions($options);
    $userMessage->save();

    $this->assertEquals(1, count(Message::getUnRead(1)));
    $userMessage->readBy(1);
    $this->assertEquals(0, count(Message::getUnRead(1)));

    //全局消息
    $options = [
      'content' => $message_content,
      'target_type' => 'globale',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $messageGlobale = Message::buildWithOptions($options);
    $messageGlobale->save();
    $this->assertEquals(1, count(Message::getUnRead(1)));
    $messageGlobale->readBy(1);
    $this->assertEquals(0, count(Message::getUnRead(1)));

    //群组发消息
    $group = new Group;
    $group->name = $this->faker->name;
    $group->save();

    $group->addUsers([1]);
    $options = [
      'content' => $message_content,
      'targets' => [1],
      'target_type' => 'group',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $groupMessage = Message::buildWithOptions($options);
    $groupMessage->save();
    $this->assertEquals(1, count(Message::getUnRead(1)));
    $groupMessage->readBy(1);
    $this->assertEquals(0, count(Message::getUnRead(1)));
  }

  //测试获取未读消息数量
  public function testGetUnReadMessageCount(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $userMessage = Message::buildWithOptions($options);
    $userMessage->save();

    $this->assertEquals(1, Message::getUnReadCount(1));
    $userMessage->readBy(1);
    $this->assertEquals(0, Message::getUnReadCount(1));
  }

  //测试获取已读消息
  public function testGetGlobaleReadMessage(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];
    $userMessage = Message::buildWithOptions($options);
    $userMessage->save();

    $this->assertEquals(1, Message::getUnReadCount(1));
    $this->assertEquals(0, count(Message::getRead(1)));
    $userMessage->readBy(1);
    $this->assertEquals(0, Message::getUnReadCount(1));
    $this->assertEquals(1, count(Message::getRead(1)));
  }
  //测试获取群发已读消息
  public function testGetUserReadMessage(){
    $message_content = 'some message content';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title'
    ];

    $userMessage = Message::buildWithOptions($options);
    $userMessage->save();

    $this->assertEquals(1, count(Message::getUnRead(1)));
    $this->assertEquals(0, count(Message::getRead(1)));
    $userMessage->readBy(1);
    $this->assertEquals(0, count(Message::getUnRead(1)));
    $this->assertEquals(1, count(Message::getRead(1)));
  }

  //测试消息带命名空间
  public function testCreateMessageWithNameSpace(){
    $message_content = 'some message content';
    $namespace = 'oem1';

    //群发用户
    $options = [
      'content' => $message_content,
      'targets' => [1, 2, 3],
      'target_type' => 'user',
      'sender_id' => 1,
      'title' => 'this is title',
      'namespace' => 'oem1'
    ];

    $userMessage = Message::buildWithOptions($options);
    $userMessage->save();

    $message = Message::first();
    $this->assertEquals($namespace, $message->namespace);
  }
}
