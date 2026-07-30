<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeDataAdjTable extends Migration
{
    /**
     * Run the migrations.
     * Time tracking table for adjusted time data (reference for payroll adjustments).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_data_adj', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('payroll_period_id');
            $table->date('date');

            // Time in/out
            $table->time('am_in')->nullable();
            $table->time('am_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('pm_in')->nullable();
            $table->time('pm_out')->nullable();

            // Work hours and deductions
            $table->decimal('work_hours', 10, 3)->default(0);
            $table->decimal('late', 10, 3)->default(0);
            $table->decimal('undertime', 10, 3)->default(0);
            $table->decimal('absent', 10, 3)->default(0);
            $table->decimal('leave', 10, 3)->default(0);

            // Official Business
            $table->boolean('is_ob')->default(false);
            $table->unsignedInteger('ob_id')->nullable();
            $table->decimal('ob_hours', 10, 3)->default(0);

            // Holiday
            $table->boolean('is_holiday')->default(false);
            $table->unsignedInteger('holiday_id')->nullable();
            $table->unsignedInteger('holiday_type_id')->nullable();
            $table->decimal('holiday_pay', 10, 2)->default(0);

            // Overtime
            $table->boolean('is_ot')->default(false);
            $table->unsignedInteger('ot_id')->nullable();
            $table->decimal('ot_hours', 10, 3)->default(0);
            $table->decimal('ot_pay', 10, 2)->default(0);
            $table->unsignedInteger('overtime_type_id')->nullable();

            // Night Differential
            $table->decimal('nd_hours', 10, 3)->default(0);
            $table->decimal('nd_pay', 10, 2)->default(0);
            $table->decimal('nd_rate', 10, 2)->default(0);
            $table->time('nd_start')->nullable();
            $table->time('nd_end')->nullable();

            // Offsets
            $table->decimal('applied_offset', 10, 3)->default(0);
            $table->decimal('late_offset', 10, 3)->default(0);
            $table->decimal('undertime_offset', 10, 3)->default(0);
            $table->decimal('absent_offset', 10, 3)->default(0);
            $table->decimal('excess_hours', 10, 3)->default(0);

            // Special flags
            $table->boolean('is_restday')->default(false);
            $table->boolean('is_shifting')->default(false);
            $table->boolean('is_wfh')->default(false);
            $table->boolean('lwop')->default(false);
            $table->boolean('for_approval')->default(false);
            $table->boolean('is_edited')->default(false);

            // Assumed/projected records
            $table->boolean('is_assumed')->default(false);
            $table->text('assumption_reason')->nullable();

            // References
            $table->unsignedInteger('work_schedule_id')->nullable();
            $table->unsignedInteger('dtr_request_id')->nullable();

            // Work from home
            $table->text('wfh_reason')->nullable();
            $table->text('wfh_location')->nullable();
            $table->unsignedInteger('wfh_approved_by')->nullable();
            $table->timestamp('wfh_approved_at')->nullable();

            // Manual entry
            $table->string('manual_entry_source', 50)->nullable();
            $table->timestamp('entry_timestamp')->nullable();

            // Attachments
            $table->string('attachment_name', 255)->nullable();
            $table->text('path')->nullable();
            $table->string('extension', 10)->nullable();

            // Metadata
            $table->text('remarks')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Adjustment tracking (link to original, target period, approval flow)
            $table->unsignedBigInteger('source_time_data_id')->nullable();
            $table->unsignedInteger('target_payroll_period_id');
            $table->string('adjustment_type', 30);
            $table->string('status', 20)->default('PENDING');
            $table->text('reason');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedInteger('applied_by')->nullable();

            $table->unique(['employee_id', 'date', 'payroll_period_id']);
        });

        Schema::table('time_data_adj', function (Blueprint $table) {
            $table->foreign('source_time_data_id')->references('id')->on('time_data');
            $table->index(['employee_id', 'payroll_period_id']);
            $table->index(['is_assumed', 'payroll_period_id']);
            $table->index(['target_payroll_period_id', 'status']);
            $table->index('source_time_data_id');
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_data_adj');
    }
}
