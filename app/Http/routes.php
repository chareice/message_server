<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->group(['prefix' => 'groups'], function () use ($app) {
  $app->get('/', ['uses' => 'App\Http\Controllers\GroupsController@index']);
  $app->post('/', ['uses' => 'App\Http\Controllers\GroupsController@create']);
  $app->post('/add_users', ['uses' => 'App\Http\Controllers\GroupsController@addUsers']);
  $app->delete('/delete_users', ['uses' => 'App\Http\Controllers\GroupsController@deleteUsers']);
  $app->get('/{group_id}', ['uses' => 'App\Http\Controllers\GroupsController@show']);
  $app->delete('/{group_id}', ['uses' => 'App\Http\Controllers\GroupsController@destroy']);
});

$app->group(['prefix' => 'messages'], function () use ($app) {
  $app->post('/', ['uses' => 'App\Http\Controllers\MessagesController@create']);
});

$app->group(['prefix' => 'users'], function() use ($app){
  $app->get('{user_id}/unread_messages', ['uses' => 'App\Http\Controllers\MessagesController@getUnReadMessage']);
});
