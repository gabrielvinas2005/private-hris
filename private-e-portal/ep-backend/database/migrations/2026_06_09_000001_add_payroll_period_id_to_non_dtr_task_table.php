<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayrollPeriodIdToNonDtrTaskTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('non_dtr_task')) {
            return;
        }

        Schema::table('non_dtr_task', function (Blueprint $table) {
            if (!Schema::hasColumn('non_dtr_task', 'payroll_period_id')) {
                $table->unsignedBigInteger('payroll_period_id')->nullable()->after('employee_id');
                $table->index('payroll_period_id');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('non_dtr_task')) {
            return;
        }

        Schema::table('non_dtr_task', function (Blueprint $table) {
            if (Schema::hasColumn('non_dtr_task', 'payroll_period_id')) {
                $table->dropIndex(['payroll_period_id']);
                $table->dropColumn('payroll_period_id');
            }
        });
    }
}
