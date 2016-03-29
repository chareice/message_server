<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Group;

class GroupApiTest extends TestCase{
  public function testGetGroups(){
    $response = $this->call('GET', '/groups');
    $this->assertEquals(200, $response->status());
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
}
