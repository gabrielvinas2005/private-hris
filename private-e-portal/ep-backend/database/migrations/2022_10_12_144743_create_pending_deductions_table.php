<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_deductions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->integer('payroll_period_id')->default(0);
            $table->integer('deduction_id')->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->boolean('is_late')->default(0);
            $table->boolean('is_undertime')->default(0);
            $table->boolean('is_absent')->default(0);
            $table->boolean('is_paid')->default(0);
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
        Schema::dropIfExists('pending_deductions');
    }
}
