<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add half-day segment support to reversal idempotency.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_cancellation_leave_reversals', function (Blueprint $table) {
            $table->string('cancel_segment', 10)->default('WHOLE')->after('work_date');

            $table->dropUnique('uq_wc_leave_rev_emp_detail_workdate');
            $table->unique(
                ['employee_id', 'leave_detail_id', 'work_date', 'cancel_segment'],
                'uq_wc_leave_rev_emp_detail_workdate_segment'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_cancellation_leave_reversals', function (Blueprint $table) {
            $table->dropUnique('uq_wc_leave_rev_emp_detail_workdate_segment');
            $table->dropColumn('cancel_segment');

            $table->unique(
                ['employee_id', 'leave_detail_id', 'work_date'],
                'uq_wc_leave_rev_emp_detail_workdate'
            );
        });
    }
};

