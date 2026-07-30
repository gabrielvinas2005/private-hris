<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtHoursToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->decimal('ob_hours')->default(0)->nullable();
            $table->decimal('ot_hours')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->dropColumn('ob_hours');
            $table->dropColumn('ot_hours');
        });
    }
}
