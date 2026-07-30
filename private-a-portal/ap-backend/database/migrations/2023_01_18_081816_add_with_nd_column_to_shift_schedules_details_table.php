<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWithNdColumnToShiftSchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->boolean('with_nd')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->dropColumn('with_nd');
        });
    }
}
