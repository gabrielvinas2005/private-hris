<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payroll_period_id');
            $table->integer('employment_id');
            $table->integer('deduction_id');
            $table->integer('employee_id');
            $table->decimal('amount')->default(0)->nullable();
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
        Schema::dropIfExists('payroll_deductions');
    }
}
