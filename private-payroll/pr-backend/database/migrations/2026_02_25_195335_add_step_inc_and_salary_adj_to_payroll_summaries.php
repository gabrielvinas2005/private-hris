<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStepIncAndSalaryAdjToPayrollSummaries extends Migration
{
    public function up()
    {
        Schema::table('payroll_summaries', function (Blueprint $table) {
            $table->decimal('step_inc_amount', 18, 2)->default(0)->after('salary');
            $table->decimal('salary_adj_amount', 18, 2)->default(0)->after('step_inc_amount');
        });
    }

    public function down()
    {
        Schema::table('payroll_summaries', function (Blueprint $table) {
            $table->dropColumn(['step_inc_amount', 'salary_adj_amount']);
        });
    }
}