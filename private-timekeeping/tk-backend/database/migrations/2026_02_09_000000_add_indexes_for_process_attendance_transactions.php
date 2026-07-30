<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to optimize all process-attendance-related transactions:
     * - time_data lookups by employee + period + date (editTimes, reprocess)
     * - time_data_summary joins and filters by period/employee
     * - leave_details joins and date range filters
     * - payroll_periods by interval + active (getPayrollPeriods)
     * - payroll_intervals by active
     *
     * @return void
     */
    public function up()
    {
        // ---------------------------------------------------------------------
        // TIME_DATA: composite for editTimes/reprocess (employee + period + date)
        // ---------------------------------------------------------------------
        Schema::table('time_data', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'payroll_period_id', 'date'],
                'idx_time_data_employee_period_date'
            );
        });

        // ---------------------------------------------------------------------
        // TIME_DATA_SUMMARY: join and filter by Payroll_Period_ID, Employee_ID
        // ---------------------------------------------------------------------
        if (Schema::hasTable('time_data_summary')) {
            Schema::table('time_data_summary', function (Blueprint $table) {
                if (Schema::hasColumn('time_data_summary', 'Payroll_Period_ID')
                    && Schema::hasColumn('time_data_summary', 'Employee_ID')) {
                    $table->index(
                        ['Payroll_Period_ID', 'Employee_ID'],
                        'idx_time_data_summary_period_employee'
                    );
                }
            });
        }

        // ---------------------------------------------------------------------
        // LEAVE_DETAILS: join on leave_id, filter by leave_date (range)
        // ---------------------------------------------------------------------
        Schema::table('leave_details', function (Blueprint $table) {
            $table->index('leave_id', 'idx_leave_details_leave_id');
            $table->index('leave_date', 'idx_leave_details_leave_date');
            $table->index(
                ['leave_id', 'leave_date'],
                'idx_leave_details_leave_id_date'
            );
        });

        // ---------------------------------------------------------------------
        // PAYROLL_PERIODS: filter by payroll_interval_id + active, order by release_date
        // ---------------------------------------------------------------------
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->index(
                ['payroll_interval_id', 'active'],
                'idx_payroll_periods_interval_active'
            );
            $table->index(
                ['payroll_interval_id', 'active', 'release_date'],
                'idx_payroll_periods_interval_active_release'
            );
        });

        // ---------------------------------------------------------------------
        // PAYROLL_INTERVALS: filter by active
        // ---------------------------------------------------------------------
        Schema::table('payroll_intervals', function (Blueprint $table) {
            $table->index('active', 'idx_payroll_intervals_active');
        });

        // ---------------------------------------------------------------------
        // EMPLOYEES: filters by branch_id, department_id, position_id (process attendance list)
        // ---------------------------------------------------------------------
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->index('branch_id', 'idx_employees_branch_id');
            }
            $table->index('department_id', 'idx_employees_department_id');
            $table->index('position_id', 'idx_employees_position_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->dropIndex('idx_time_data_employee_period_date');
        });

        if (Schema::hasTable('time_data_summary')
            && Schema::hasColumn('time_data_summary', 'Payroll_Period_ID')
            && Schema::hasColumn('time_data_summary', 'Employee_ID')) {
            Schema::table('time_data_summary', function (Blueprint $table) {
                $table->dropIndex('idx_time_data_summary_period_employee');
            });
        }

        Schema::table('leave_details', function (Blueprint $table) {
            $table->dropIndex('idx_leave_details_leave_id');
            $table->dropIndex('idx_leave_details_leave_date');
            $table->dropIndex('idx_leave_details_leave_id_date');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_periods_interval_active');
            $table->dropIndex('idx_payroll_periods_interval_active_release');
        });

        Schema::table('payroll_intervals', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_intervals_active');
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->dropIndex('idx_employees_branch_id');
            }
            $table->dropIndex('idx_employees_department_id');
            $table->dropIndex('idx_employees_position_id');
        });
    }
};
