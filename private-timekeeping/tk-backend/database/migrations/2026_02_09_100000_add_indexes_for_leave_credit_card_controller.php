<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to optimize all SQL used by LeaveCreditCardController
     * (Leave Credit Card Monitoring view).
     *
     * Tables/queries covered:
     * - leave_types: getLeaveTypes, details, getLeaveCredits (active, id+active)
     * - leave_earnings: raw SQL subquery by days_present
     * - leave_beginning_balances: details() by employee_id + leave_type_id, order year/month_id
     * - leave_credits: getLeaveCredits earliest record per employee+leave_type (created_at)
     * - leave_headers: deductLeaves, getLeaveDeductions (employee_id, leave_type_id, approval filters)
     *
     * @return void
     */
    public function up()
    {
        // ---------------------------------------------------------------------
        // LEAVE_TYPES: where active=1, where id=X and active=1
        // ---------------------------------------------------------------------
        Schema::table('leave_types', function (Blueprint $table) {
            $table->index('active', 'idx_leave_types_active');
        });

        // ---------------------------------------------------------------------
        // LEAVE_EARNINGS: subquery where days_present = (expression)
        // ---------------------------------------------------------------------
        Schema::table('leave_earnings', function (Blueprint $table) {
            $table->index('days_present', 'idx_leave_earnings_days_present');
        });

        // ---------------------------------------------------------------------
        // LEAVE_BEGINNING_BALANCES: where employee_id, leave_type_id, order year, month_id
        // ---------------------------------------------------------------------
        if (Schema::hasTable('leave_beginning_balances')) {
            Schema::table('leave_beginning_balances', function (Blueprint $table) {
                if (Schema::hasColumn('leave_beginning_balances', 'employee_id')
                    && Schema::hasColumn('leave_beginning_balances', 'leave_type_id')) {
                    $table->index(
                        ['employee_id', 'leave_type_id'],
                        'idx_leave_beginning_balances_employee_leave_type'
                    );
                }
                if (Schema::hasColumn('leave_beginning_balances', 'year')
                    && Schema::hasColumn('leave_beginning_balances', 'month_id')) {
                    $table->index(
                        ['employee_id', 'leave_type_id', 'year', 'month_id'],
                        'idx_leave_beginning_balances_employee_type_year_month'
                    );
                }
            });
        }

        // ---------------------------------------------------------------------
        // LEAVE_CREDITS: where employee_id + leave_type_id, orderBy created_at (earliest)
        // ---------------------------------------------------------------------
        Schema::table('leave_credits', function (Blueprint $table) {
            if (Schema::hasColumn('leave_credits', 'created_at')) {
                $table->index(
                    ['employee_id', 'leave_type_id', 'created_at'],
                    'idx_leave_credits_employee_type_created'
                );
            }
        });

        // ---------------------------------------------------------------------
        // LEAVE_HEADERS: deductLeaves/getLeaveDeductions - employee_id, leave_type_id, approved*
        // ---------------------------------------------------------------------
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'leave_type_id'],
                'idx_leave_headers_employee_leave_type'
            );
            $table->index(
                ['employee_id', 'leave_type_id', 'approved', 'approved_2', 'approved_3'],
                'idx_leave_headers_employee_type_approved'
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
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropIndex('idx_leave_types_active');
        });

        Schema::table('leave_earnings', function (Blueprint $table) {
            $table->dropIndex('idx_leave_earnings_days_present');
        });

        if (Schema::hasTable('leave_beginning_balances')) {
            Schema::table('leave_beginning_balances', function (Blueprint $table) {
                if (Schema::hasColumn('leave_beginning_balances', 'employee_id')
                    && Schema::hasColumn('leave_beginning_balances', 'leave_type_id')) {
                    $table->dropIndex('idx_leave_beginning_balances_employee_leave_type');
                }
                if (Schema::hasColumn('leave_beginning_balances', 'year')
                    && Schema::hasColumn('leave_beginning_balances', 'month_id')) {
                    $table->dropIndex('idx_leave_beginning_balances_employee_type_year_month');
                }
            });
        }

        Schema::table('leave_credits', function (Blueprint $table) {
            if (Schema::hasColumn('leave_credits', 'created_at')) {
                $table->dropIndex('idx_leave_credits_employee_type_created');
            }
        });

        Schema::table('leave_headers', function (Blueprint $table) {
            $table->dropIndex('idx_leave_headers_employee_leave_type');
            $table->dropIndex('idx_leave_headers_employee_type_approved');
        });
    }
};
