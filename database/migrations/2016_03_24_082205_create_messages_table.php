<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function(Blueprint $table) {
          //消息ID
          $table->increments('id');

          //消息标题
          $table->string('title');

          //消息内容
          $table->text('content');

          //发送者ID
          $table->string('sender_id')->index();

          //目标类型 可能是用户／群组／全体
          $table->enum('target_type', ['user', 'group', 'globale']);

          //消息类型 可能是单发／多发／组发／全局发
          $table->enum('message_type', ['unicast', 'mutipcast', 'groupcast', 'globalecast'])->index();;
          //created_at and updated_at
          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::drop('messages');
    }
}
