<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Group;
use App\GroupTarget;

class GroupTest extends TestCase{

  //创建群组测试
  public function testCreateGroup(){
    $group = new Group;
    $group->name = $this->faker->name;
    assert($group->save());
  }

  //群组添加用户测试
  public function testAddUsersToGroup(){
    $group = new Group;
    $group->name = $this->faker->name;
    $group->save();

    $this->assertEquals(0, $group->targets()->count());

    $group->addUsers([1, 2, 3]);

    $this->assertEquals(3, $group->targets()->count());

    $group->addUsers([4]);
    $this->assertEquals(4, $group->targets()->count());
  }

  //群组删除用户测试
  public function testRemoveUsersFromGroup(){
    $group = new Group;
    $group->name = $this->faker->name;
    $group->save();
    
    $group2 = new Group;
    $group2->name = $this->faker->name;
    $group2->save();

    $group->addUsers([1, 2, 3]);
    $this->assertEquals(3, $group->targets()->count());

    $group2->addUsers([1, 2, 3]);
    $this->assertEquals(3, $group2->targets()->count());

    //删除组的用户
    $group->removeUsers([1, 2]);
    $this->assertEquals(1, $group->targets()->count());
    //组2的用户不会有变化
    $this->assertEquals(3, $group2->targets()->count());
  }

  //群组清空用户测试
  public function testEmptyUsersFromGroups(){

  }
}
