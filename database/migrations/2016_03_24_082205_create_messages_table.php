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

          //消息内容
          $table->text('content')->index();

          //发送者ID
          $table->integer('sender_id')->index();

          //目标类型 可能是多发／组发
          $table->string('target_type')->index();

          //created_at and updated_at
          $table->timestamps();
        });

        //target如果是多发的情况将接受者ID存到数组中
        DB::statement('ALTER TABLE messages ADD COLUMN targets integer[]');
        DB::statement('CREATE INDEX messages_targets_index on "messages" USING GIN ("targets");');
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
