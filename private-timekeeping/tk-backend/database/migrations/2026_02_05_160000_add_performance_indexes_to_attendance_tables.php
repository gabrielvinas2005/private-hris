<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add performance indexes to optimize attendance and payroll processing queries.
     * These indexes target common query patterns in the attendance system.
     *
     * @return void
     */
    public function up()
    {
        // Check for optional columns before creating indexes
        $hasIsWfh = Schema::hasColumn('time_data', 'is_wfh');
        $hasAppliedOffset = Schema::hasColumn('time_data', 'applied_offset');

        // =====================================================================
        // TIME_DATA TABLE INDEXES
        // =====================================================================
        Schema::table('time_data', function (Blueprint $table) use ($hasIsWfh, $hasAppliedOffset) {
            // Core lookup indexes - optimized for common WHERE clauses
            // Most selective columns first for better index performance
            $table->index(['employee_id', 'date'], 'idx_time_data_employee_date');
            $table->index(['date', 'absent'], 'idx_time_data_date_absent');
            
            // Conditional indexes - only add if column exists
            if ($hasIsWfh) {
                $table->index(['date', 'is_wfh'], 'idx_time_data_date_wfh');
            }
            
            $table->index(['date', 'leave'], 'idx_time_data_date_leave');
            $table->index(['date', 'is_holiday'], 'idx_time_data_date_holiday');
            $table->index(['date', 'is_ob'], 'idx_time_data_date_ob');
            
            // Processing cursor index - optimizes SP_ProcessTimeData queries
            // Order: date (most selective) -> applied_offset -> leave -> is_holiday -> is_ob
            if ($hasAppliedOffset) {
                $table->index(['date', 'applied_offset', 'leave', 'is_holiday', 'is_ob'], 'idx_time_data_processing');
            }
            
            // Payroll period index - different from existing [payroll_period_id, employee_id]
            // This one prioritizes date for period-based date range queries
            $table->index(['payroll_period_id', 'date'], 'idx_time_data_payroll_period');
        });

        // =====================================================================
        // EMPLOYEES TABLE INDEXES
        // =====================================================================
        Schema::table('employees', function (Blueprint $table) {
            // Schedule and shifting lookup - optimizes employee schedule queries
            $table->index(['work_schedule_id', 'is_shifting'], 'idx_employees_schedule_shifting');
            $table->index('is_shifting', 'idx_employees_is_shifting');
        });

        // =====================================================================
        // FIX SCHEDULES DETAILS TABLE INDEXES
        // =====================================================================
        Schema::table('fix_schedules_details', function (Blueprint $table) {
            // Schedule day lookup - optimizes schedule detail retrieval
            $table->index(['fix_schedule_id', 'day_id'], 'idx_fix_schedules_schedule_day');
            
            // Restday lookup - optimizes restday queries
            $table->index(['fix_schedule_id', 'day_id', 'is_restday'], 'idx_fix_schedules_schedule_day_rest');
        });

        // =====================================================================
        // SHIFT SCHEDULES DETAILS TABLE INDEXES
        // =====================================================================
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            // Shift date lookup - optimizes shift schedule queries
            $table->index(['shift_schedule_id', 'shift_date'], 'idx_shift_schedules_schedule_date');
            $table->index('shift_date', 'idx_shift_schedules_date');
        });

        // =====================================================================
        // HOLIDAYS TABLE INDEXES
        // =====================================================================
        Schema::table('holidays', function (Blueprint $table) {
            // Date lookup - optimizes holiday date queries
            $table->index('date', 'idx_holidays_date');
        });

        // =====================================================================
        // LEAVE HEADERS TABLE INDEXES
        // =====================================================================
        Schema::table('leave_headers', function (Blueprint $table) {
            // Employee leave lookup - optimizes leave retrieval by employee and date range
            $table->index(['employee_id', 'date_from', 'date_to'], 'idx_leave_headers_employee_dates');
            
            // Approved leave lookup - optimizes approval status queries
            $table->index(['date_from', 'date_to', 'approved', 'approved_2', 'approved_3'], 'idx_leave_headers_dates_approved');
        });

        // =====================================================================
        // OFFICIAL BUSINESS APPLICATIONS TABLE INDEXES
        // =====================================================================
        Schema::table('official_business_applications', function (Blueprint $table) {
            // Employee OB lookup - optimizes OB retrieval by employee and date range
            $table->index(['employee_id', 'date_time_from', 'date_time_to'], 'idx_ob_employee_dates');
            
            // Approval status lookup - optimizes approval queries
            $table->index(['approved', 'approved_2', 'approved_3'], 'idx_ob_approved');
        });

        // =====================================================================
        // WORK CANCELLATIONS TABLE INDEXES
        // =====================================================================
        Schema::table('work_cancellations', function (Blueprint $table) {
            // Date range lookup - optimizes work cancellation date queries
            $table->index(['date_from', 'date_to'], 'idx_work_cancellations_dates');
        });

        // =====================================================================
        // OVERTIME APPLICATIONS TABLE INDEXES
        // =====================================================================
        Schema::table('overtime_applications', function (Blueprint $table) {
            // Employee OT lookup - optimizes OT retrieval by employee and date
            $table->index(['employee_id', 'date'], 'idx_overtime_employee_date');
            
            // Approved OT lookup - optimizes approved OT queries
            $table->index(['employee_id', 'date', 'approved', 'approved_2', 'approved_3'], 'idx_overtime_employee_date_approved');
        });

        // =====================================================================
        // PAYROLL PERIODS TABLE INDEXES
        // =====================================================================
        Schema::table('payroll_periods', function (Blueprint $table) {
            // Active period lookup - optimizes active payroll period queries
            $table->index(['attendance_start_date', 'attendance_end_date', 'active'], 'idx_payroll_periods_dates_active');
            $table->index('active', 'idx_payroll_periods_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop all indexes added in the up() method.
     *
     * @return void
     */
    public function down()
    {
        // Check for optional columns before dropping indexes
        $hasIsWfh = Schema::hasColumn('time_data', 'is_wfh');
        $hasAppliedOffset = Schema::hasColumn('time_data', 'applied_offset');

        Schema::table('time_data', function (Blueprint $table) use ($hasIsWfh, $hasAppliedOffset) {
            $table->dropIndex('idx_time_data_employee_date');
            $table->dropIndex('idx_time_data_date_absent');
            
            // Only drop indexes that were conditionally created
            if ($hasIsWfh) {
                $table->dropIndex('idx_time_data_date_wfh');
            }
            
            $table->dropIndex('idx_time_data_date_leave');
            $table->dropIndex('idx_time_data_date_holiday');
            $table->dropIndex('idx_time_data_date_ob');
            
            if ($hasAppliedOffset) {
                $table->dropIndex('idx_time_data_processing');
            }
            
            $table->dropIndex('idx_time_data_payroll_period');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_schedule_shifting');
            $table->dropIndex('idx_employees_is_shifting');
        });

        Schema::table('fix_schedules_details', function (Blueprint $table) {
            $table->dropIndex('idx_fix_schedules_schedule_day');
            $table->dropIndex('idx_fix_schedules_schedule_day_rest');
        });

        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->dropIndex('idx_shift_schedules_schedule_date');
            $table->dropIndex('idx_shift_schedules_date');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('idx_holidays_date');
        });

        Schema::table('leave_headers', function (Blueprint $table) {
            $table->dropIndex('idx_leave_headers_employee_dates');
            $table->dropIndex('idx_leave_headers_dates_approved');
        });

        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->dropIndex('idx_ob_employee_dates');
            $table->dropIndex('idx_ob_approved');
        });

        Schema::table('work_cancellations', function (Blueprint $table) {
            $table->dropIndex('idx_work_cancellations_dates');
        });

        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->dropIndex('idx_overtime_employee_date');
            $table->dropIndex('idx_overtime_employee_date_approved');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_periods_dates_active');
            $table->dropIndex('idx_payroll_periods_active');
        });
    }
};
