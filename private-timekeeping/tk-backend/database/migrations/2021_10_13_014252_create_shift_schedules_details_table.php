<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftSchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_schedules_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('shift_schedule_id')->default(0);
            $table->date('shift_date')->nullable();
            $table->time('am_in')->nullable();
            $table->time('am_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('pm_in')->nullable();
            $table->time('pm_out')->nullable();
            $table->decimal('grace_period')->default(0)->nullable();
            $table->decimal('flexi_hours')->default(0)->nullable();
            $table->decimal('work_hours')->default(0)->nullable();
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
        Schema::dropIfExists('shift_schedules_details');
    }
}
