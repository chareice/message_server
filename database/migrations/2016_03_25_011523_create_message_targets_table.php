<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_targets', function(Blueprint $table) {
          $table->integer('message_id')->unsigned()->index();
          $table->integer('target_id')->unsigned()->index();

          $table->foreign('message_id')->references('id')->on('messages');
          $table->timestamps();
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
        Schema::drop('message_targets');
    }
}
