<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payroll_interval_id')->default(0);
            $table->integer('payroll_cutoff_id')->default(0);
            $table->date('attendance_start_date')->nullable();
            $table->date('attendance_end_date')->nullable();
            $table->date('payroll_start_date')->nullable();
            $table->date('payroll_end_date')->nullable();
            $table->date('release_date')->nullable();
            $table->boolean('posted')->default(0)->nullable();
            $table->boolean('active')->default(0)->nullable();
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
        Schema::dropIfExists('payroll_periods');
    }
}
