<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds OT_Pay column so the OT amount computed in ProcessAttendanceController
     * is stored in time_data_summary (same value as Overtime amount).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->decimal('OT_Pay', 18, 3)->default(0)->after('Overtime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->dropColumn('OT_Pay');
        });
    }
};
