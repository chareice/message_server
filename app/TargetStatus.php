<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TargetStatus extends Model{
  protected $table = 'target_status';
  public $timestamps = false;


  public static function boot(){
    parent::boot();

    static::created(function($model){
      $model->created_at = Carbon::now();
    });
  }

  public function message()
  {
    return $this->belongsTo('App\Message');
  }
}
