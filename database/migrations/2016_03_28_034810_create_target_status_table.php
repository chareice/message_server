<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTargetStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('target_status', function(Blueprint $table) {
        $table->integer('message_id')->unsigned()->index();
        $table->string('target_id')->index();
        //未读，已读，已删除
        $table->enum('status', ['unread', 'read', 'deleted']);
        $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');;
        $table->primary(['message_id', 'target_id']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('target_status');
    }
}
