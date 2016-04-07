<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEffectiveTimeAndExpirationTimeToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('messages', function ($table) {
        //生效时间
        $table->dateTime('effective_time')->nullable()->index();
        //过期时间
        $table->dateTime('expiration_time')->nullable()->index();
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
        //生效时间
        $table->dropColumn('effective_time');
        //过期时间
        $table->dropColumn('expiration_time');
      });
    }
}
