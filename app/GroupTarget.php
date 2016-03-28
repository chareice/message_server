<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupTarget extends Model{
  public $timestamps = false;

  protected $fillable = ['target_id'];

  public function group(){
    return $this->belongs_to('App\Group');
  }
}
