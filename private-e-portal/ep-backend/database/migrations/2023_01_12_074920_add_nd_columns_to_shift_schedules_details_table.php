<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNdColumnsToShiftSchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->time('nd_start', 0)->nullable();
            $table->time('nd_end', 0)->nullable();
            $table->decimal('nd_rate', 18, 2)->default(0)->nullable();
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
            $table->dropColumn('nd_start');
            $table->dropColumn('nd_end');
            $table->dropColumn('nd_rate');
        });
    }
}
