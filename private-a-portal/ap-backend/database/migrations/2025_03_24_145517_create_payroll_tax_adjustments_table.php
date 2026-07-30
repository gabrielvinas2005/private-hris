<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollTaxAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_tax_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payroll_period_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
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
        Schema::dropIfExists('payroll_tax_adjustments');
    }
}
