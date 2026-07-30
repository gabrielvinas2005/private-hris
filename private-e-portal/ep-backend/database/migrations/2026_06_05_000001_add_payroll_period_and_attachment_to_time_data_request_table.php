<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayrollPeriodAndAttachmentToTimeDataRequestTable extends Migration
{
    public function up()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            if (!Schema::hasColumn('time_data_request', 'payroll_period_id')) {
                $table->integer('payroll_period_id')->default(0)->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'attachment_name')) {
                $table->string('attachment_name', 255)->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'path')) {
                $table->string('path', 500)->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'extension')) {
                $table->string('extension', 20)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            $columns = ['payroll_period_id', 'attachment_name', 'path', 'extension'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('time_data_request', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
