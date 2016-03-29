<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function responseJson($data){
      $res = [
        'data' => $data
      ];
      return response()->json($res);
    }
}
