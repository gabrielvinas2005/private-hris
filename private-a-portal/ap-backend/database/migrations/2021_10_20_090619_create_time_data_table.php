<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->integer('payroll_period_id')->default(0);
            $table->date('date')->nullable();
            $table->time('am_in')->nullable();
            $table->time('am_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('pm_in')->nullable();
            $table->time('pm_out')->nullable();
            $table->decimal('work_hours')->default(0)->nullable();
            $table->decimal('late', 18, 3);
            $table->decimal('undertime', 18, 3);
            $table->decimal('absent')->default(0);
            $table->decimal('leave')->default(0);
            $table->boolean('is_ob')->default(0);
            $table->integer('ob_id')->default(0);
            $table->boolean('is_holiday')->default(0);
            $table->integer('holiday_id')->default(0);
            $table->decimal('holiday_pay')->default(0);
            $table->boolean('is_ot')->default(0);
            $table->integer('ot_id')->default(0);
            $table->decimal('ot_pay')->default(0);
            $table->decimal('nd_pay')->default(0);
            $table->string('remarks')->default(0);
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
        Schema::dropIfExists('time_data');
    }
}
