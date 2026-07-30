<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to optimize OT Monitoring, OB Monitoring, and Leave Monitoring
     * entry retrievals (OvertimeApplicationController::monitoring,
     * OfficialBusinessApplicationController::monitoring, LeaveController::monitoring).
     *
     * OT/OB: monitoring queries filter by date >= today and status columns
     * (approved_3, disapproved, disapproved_2, disapproved_3, is_cancel).
     * Leave: monitoring joins leave_headers with approver_details/approver_headers
     * (type_id = 1); approver subqueries use type_id and employee_id.
     *
     * @return void
     */
    public function up()
    {
        // ---------------------------------------------------------------------
        // OVERTIME_APPLICATIONS: monitoring WHERE date >= today AND status cols
        // ---------------------------------------------------------------------
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->index(
                ['date', 'approved_3', 'disapproved', 'disapproved_2', 'disapproved_3', 'is_cancel'],
                'idx_overtime_monitoring'
            );
        });

        // ---------------------------------------------------------------------
        // OFFICIAL_BUSINESS_APPLICATIONS: monitoring WHERE date >= today AND status
        // ---------------------------------------------------------------------
        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->index(
                ['date', 'approved_3', 'disapproved', 'disapproved_2', 'disapproved_3', 'is_cancel'],
                'idx_ob_monitoring'
            );
        });

        // ---------------------------------------------------------------------
        // APPROVER_HEADERS: subquery INNER JOIN ... AND ah.type_id = 1|2|3
        // Used by OT (type_id=3), OB (type_id=2), Leave (type_id=1) monitoring
        // ---------------------------------------------------------------------
        Schema::table('approver_headers', function (Blueprint $table) {
            if (Schema::hasColumn('approver_headers', 'type_id')) {
                $table->index('type_id', 'idx_approver_headers_type_id');
            }
        });

        // ---------------------------------------------------------------------
        // APPROVER_DETAILS: subquery join ad.employee_id = employee.id, ah.id = ad.approver_id
        // ---------------------------------------------------------------------
        Schema::table('approver_details', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'approver_id'],
                'idx_approver_details_employee_approver'
            );
        });

        // Leave monitoring: leave_headers already has idx_leave_headers_employee_dates and
        // idx_leave_headers_dates_approved (date_from leading) from 2026_02_05; leave_credits
        // and leave_details are indexed elsewhere. No additional leave_headers index needed.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->dropIndex('idx_overtime_monitoring');
        });

        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->dropIndex('idx_ob_monitoring');
        });

        Schema::table('approver_headers', function (Blueprint $table) {
            if (Schema::hasColumn('approver_headers', 'type_id')) {
                $table->dropIndex('idx_approver_headers_type_id');
            }
        });

        Schema::table('approver_details', function (Blueprint $table) {
            $table->dropIndex('idx_approver_details_employee_approver');
        });

    }
};
