<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track long-running attendance processing runs so we can request cancellation
     * from the UI while the same request is inside a DB transaction.
     */
    public function up()
    {
        Schema::create('attendance_process_runs', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('payroll_period_id')->nullable();
            $table->integer('employee_id')->nullable();

            // running -> cancel_requested -> cancelled / completed
            $table->string('status', 32)->default('running');

            $table->dateTime('started_at')->nullable();
            $table->dateTime('cancel_requested_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['payroll_period_id'], 'idx_attendance_process_runs_period');
            $table->index(['status'], 'idx_attendance_process_runs_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_process_runs');
    }
};

