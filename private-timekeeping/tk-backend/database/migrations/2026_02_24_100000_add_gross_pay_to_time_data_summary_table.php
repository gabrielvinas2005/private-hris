<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Gross_Pay column: (Salary/22 * (Days_Present + Is_Adjusted count)) + OT_Pay + Holiday_Pay.
     * Not displayed in UI; stored for reporting/audit.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->decimal('Gross_Pay', 18, 3)->nullable()->after('Total_Amount');
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
            $table->dropColumn('Gross_Pay');
        });
    }
};
