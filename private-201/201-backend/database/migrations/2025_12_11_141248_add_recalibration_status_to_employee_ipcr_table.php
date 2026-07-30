<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecalibrationStatusToEmployeeIpcrTable extends Migration
{
    public function up()
    {
        Schema::table('employee_ipcr', function (Blueprint $table) {
            // Recalibration workflow status
            $table->string('recalibration_status', 50)
                  ->default('self_assessment')
                  ->after('final_rate_date');

            // Timestamps for each recalibration level
            $table->timestamp('supervisor_recalibrated_at')->nullable()->after('recalibration_status');
            $table->timestamp('hr_recalibrated_at')->nullable()->after('supervisor_recalibrated_at');
            $table->timestamp('pmt_recalibrated_at')->nullable()->after('hr_recalibrated_at');

            // Index for faster queries
            $table->index('recalibration_status');
        });
    }

    public function down()
    {
        Schema::table('employee_ipcr', function (Blueprint $table) {
            $table->dropIndex(['recalibration_status']);
            $table->dropColumn([
                'recalibration_status',
                'supervisor_recalibrated_at',
                'hr_recalibrated_at',
                'pmt_recalibrated_at'
            ]);
        });
    }
}
