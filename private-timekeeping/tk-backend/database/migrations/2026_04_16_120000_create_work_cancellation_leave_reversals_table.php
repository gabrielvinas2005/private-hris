<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotent log of leave credits restored when whole-day work cancellation
     * is applied during attendance processing (SP_ProcessTimeData).
     *
     * Prevents double-crediting on reprocess: insert only when no row exists for
     * the same employee, leave detail line, and calendar date.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_cancellation_leave_reversals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_header_id')->nullable();
            $table->unsignedBigInteger('leave_detail_id');
            $table->unsignedInteger('leave_type_id');
            $table->date('work_date');
            $table->unsignedBigInteger('work_cancellation_id')->nullable();
            $table->unsignedInteger('payroll_period_id')->nullable();
            $table->decimal('revert_qty', 18, 3);
            $table->timestamps();

            $table->unique(
                ['employee_id', 'leave_detail_id', 'work_date'],
                'uq_wc_leave_rev_emp_detail_workdate'
            );

            $table->index('work_date', 'idx_wc_leave_rev_work_date');
            $table->index(['employee_id', 'leave_type_id'], 'idx_wc_leave_rev_emp_leavetype');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_cancellation_leave_reversals');
    }
};
