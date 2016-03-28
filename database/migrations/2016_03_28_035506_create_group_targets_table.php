<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('group_targets', function(Blueprint $table) {
        $table->integer('group_id')->unsigned()->index();
        $table->integer('target_id')->unsigned()->index();

        $table->foreign('group_id')->references('id')->on('groups');
        $table->primary(['group_id', 'target_id']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('group_targets');
    }
}
