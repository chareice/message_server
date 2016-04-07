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
        $table->string('target_id')->index();

        $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');;
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
