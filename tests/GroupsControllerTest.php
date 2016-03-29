<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Group;

class GroupsControllerTest extends TestCase{
  public function testGetGroups(){
    $response = $this->call('GET', '/groups');
    $this->assertResponseOk();
    $this->assertEquals(0, count($response->getData()->data));

    #create groups
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

    $response = $this->call('GET', '/groups');
    $this->assertEquals(10, count($response->getData()->data));
  }

  //测试创建用户组
  public function testCreateGroup(){
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);
    $this->assertResponseOk();
  }

  //测试向组中添加用户
  public function testAddGroupUser(){
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);

    $group = Group::first();
    $this->assertEquals(0, $group->targets()->count());

    $options = [
      'users' => [1, 2, 3],
      'group_id' => $group->id
    ];
    $response = $this->call('POST', '/groups/add_users', $options);
    $this->assertResponseOk();
    //reload group
    $group = Group::first();
    $this->assertEquals(count($options['users']), $group->targets()->count());
  }

  //测试获取Group信息
  public function testGetGroupInfo(){
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);

    $group = Group::first();
    $this->assertEquals(0, $group->targets()->count());

    $options = [
      'users' => [1, 2, 3],
      'group_id' => $group->id
    ];
    $response = $this->call('POST', '/groups/add_users', $options);
    $this->assertResponseOk();

    $response = $this->call('get', '/groups/'.$group->id);
    $this->assertResponseOk();
    $this->assertEquals(3, count($response->getData()->data->targets));
  }

  //测试从Group删除用户
  public function testDeleteUsersFromGroup(){
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);

    $group = Group::first();
    $this->assertEquals(0, $group->targets()->count());

    $options = [
      'users' => [1, 2, 3],
      'group_id' => $group->id
    ];
    $response = $this->call('POST', '/groups/add_users', $options);
    $this->assertResponseOk();

    $options = [
      'users' => [1, 3],
      'group_id' => $group->id
    ];

    $response = $this->call('DELETE', '/groups/delete_users', $options);
    $this->assertResponseOk();

    $response = $this->call('get', '/groups/'.$options['group_id']);
    $this->assertResponseOk();
    $this->assertEquals(1, count($response->getData()->data->targets));
  }

  //删除组测试
  public function testDeleteGroup(){
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);

    $group = Group::first();

    $response = $this->call('DELETE', '/groups/'.$group->id);
    $this->assertResponseOk();
    $this->assertEquals(0, Group::count());

    //删除有targets的用户组
    $options = [
      'name' => $this->faker->name
    ];

    $response = $this->call('POST', '/groups', $options);

    $group = Group::first();
    $group->addUsers([1,2,3,4]);

    $response = $this->call('DELETE', '/groups/'.$group->id);
    $this->assertResponseOk();
    $this->assertEquals(0, Group::count());
  }
}
