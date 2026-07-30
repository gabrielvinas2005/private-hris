<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollSummariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_summaries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payroll_period_id')->default(0)->nullable();
            $table->integer('employee_id')->default(0)->nullable();
            $table->decimal('salary')->default(0)->nullable();
            $table->decimal('gsis')->default(0)->nullable();
            $table->decimal('sss')->default(0)->nullable();
            $table->decimal('pagibig')->default(0)->nullable();
            $table->decimal('philhealth')->default(0)->nullable();
            $table->decimal('tax')->default(0)->nullable();
            $table->decimal('late_amount')->default(0)->nullable();
            $table->decimal('ut_amount')->default(0)->nullable();
            $table->decimal('absent_amount')->default(0)->nullable();
            $table->decimal('holiday_amount')->default(0)->nullable();
            $table->decimal('ot_amount')->default(0)->nullable();
            $table->decimal('nd_amount')->default(0)->nullable();
            $table->decimal('total_income')->default(0)->nullable();
            $table->decimal('total_deduction')->default(0)->nullable();
            $table->decimal('gross_amount')->default(0)->nullable();
            $table->decimal('net_pay')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_summaries');
    }
}
