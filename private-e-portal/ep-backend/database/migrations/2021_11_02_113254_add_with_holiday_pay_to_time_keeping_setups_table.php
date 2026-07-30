<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWithHolidayPayToTimeKeepingSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            $table->boolean('with_holiday_pay')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            $table->dropColumn('with_holiday_pay');
        });
    }
}
