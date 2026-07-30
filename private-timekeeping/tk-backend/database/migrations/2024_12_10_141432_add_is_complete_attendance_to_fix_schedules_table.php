<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCompleteAttendanceToFixSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fix_schedules', function (Blueprint $table) {
            $table->boolean('is_complete_attendance')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fix_schedules', function (Blueprint $table) {
            $table->dropColumn('is_complete_attendance');
        });
    }
}
