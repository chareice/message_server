<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Group;

class GroupsController extends Controller
{
  public function index(){
    $groups = Group::all();
    return $this->responseJson($groups);
  }

  //创建用户组
  public function create(Request $request){
    $group_name = $request->input('name');
    $group = new Group;
    $group->name = $group_name;
    if($group->save()){
      return $this->responseJson($group);
    }
  }

  //向组中添加用户
  public function addUsers(Request $request){
    $group_id = $request->input('group_id');
    $group = Group::find($group_id);
    $usersForAdd = $request->input('users');
    $group->addUsers($usersForAdd);
    return $this->responseJson();
  }

  //从组中删除用户
  public function deleteUsers(Request $request){
    $group_id = $request->input('group_id');
    $group = Group::find($group_id);
    $usersForDelete = $request->input('users');
    $group->removeUsers($usersForDelete);
    return $this->responseJson();
  }

  //获取组信息
  public function show($group_id){
    // $group = Group::where('id', $group_id)->with('targets.target_id');
    $group = Group::with('targets')->where('id', $group_id)->first();
    $data = $group->toArray();
    $data['targets'] = collect($data['targets'])->map(function($target){
      return $target['target_id'];
    });
    return $this->responseJson($data);
  }
}
