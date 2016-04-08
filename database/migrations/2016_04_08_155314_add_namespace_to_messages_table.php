<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNamespaceToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('messages', function ($table) {
        //消息的命名空间 可以为不同的消息系统作区分
        //默认为main namespace
        $table->string('namespace')->default('main')->index();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('messages', function ($table) {
        $table->dropColumn('namespace');
      });
    }
}
