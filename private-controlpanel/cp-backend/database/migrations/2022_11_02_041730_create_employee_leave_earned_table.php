<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeLeaveEarnedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_leave_earned', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->nullable();
            $table->integer('payroll_period_id')->nullable();
            $table->integer('month_id')->nullable();
            $table->integer('year_id')->nullable();
            $table->decimal('vl_earned', 18, 3)->nullable();
            $table->decimal('sl_earned', 18, 3)->nullable();
            $table->decimal('absent')->nullable();
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
        Schema::dropIfExists('employee_leave_earned');
    }
}
