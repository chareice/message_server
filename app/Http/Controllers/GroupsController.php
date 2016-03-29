<?php

namespace App\Http\Controllers;
use App\Group;

class GroupsController extends Controller
{
  public function index(){
    $groups = Group::all();
    return $this->responseJson($groups);
  }
}
