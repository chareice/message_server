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
        $table->integer('target_id')->unsigned()->index();
        //未读，已读，已删除
        $table->enum('status', ['unread', 'read', 'deleted']);
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
