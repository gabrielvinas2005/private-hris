<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Hourly_Rate column so the hourly rate from computeRateMetrics
     * is stored in time_data_summary (used by ProcessAttendanceController save and bulk insert).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->decimal('Hourly_Rate', 18, 3)->default(0)->after('Daily');
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
            $table->dropColumn('Hourly_Rate');
        });
    }
};
