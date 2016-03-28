<?php
namespace App;
use DB;

use Illuminate\Database\Eloquent\Model;

class Group extends Model{
  public function targets(){
    return $this->hasMany('App\GroupTarget');
  }

  public function addUsers($target_ids){
    $targets = collect($target_ids)->map(function($target_id){
      $group_target = new GroupTarget(['target_id' => $target_id]);
      $group_target->group_id = $this->id;
      return $group_target;
    });

    DB::transaction(function() use ($targets){
      $this->targets()->saveMany($targets);
    });
  }

  public function removeUsers($removing_target_ids){
    $this->targets()->whereIn('target_id', $removing_target_ids)->delete();
  }
}
