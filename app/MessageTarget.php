<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class MessageTarget extends Model{
  protected $fillable = ['target_id'];
  public $timestamps = false;
}
