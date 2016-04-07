<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function responseJson($data=[], $meta=[]){
      $res = [
        'data' => $data,
        'meta' => $meta
      ];
      return response()->json($res);
    }
}
