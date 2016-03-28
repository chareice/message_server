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

    $this->assertEquals(0, $group->targets->count());

    $group->addUsers([1, 2, 3]);;

    $this->assertEquals(3, $group->targets()->count());
  }

  //群组删除用户测试
  public function testRemoveUsersFromGroup(){

  }

  //群组清空用户测试
  public function testEmptyUsersFromGroups(){

  }
}
