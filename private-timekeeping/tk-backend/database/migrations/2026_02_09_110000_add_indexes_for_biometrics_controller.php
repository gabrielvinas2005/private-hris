<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to optimize SQL used by BiometricsController (Biometrics Data view).
     * Only main-database tables are indexed; Anviz/linked-server tables (userinfo,
     * checkinout, TimeTable) are on another server and are not modified here.
     *
     * Tables/queries covered:
     * - employees: index() list (active, is_employee); dailySummary LEFT JOIN (access_no)
     * - departments: dailySummary LEFT JOIN (d.active = 1)
     *
     * fix_schedules_details, leave_headers, official_business_applications are already
     * indexed in 2026_02_05 performance migration.
     *
     * @return void
     */
    public function up()
    {
        // ---------------------------------------------------------------------
        // EMPLOYEES: index() filters active=1, is_employee=1; dailySummary same
        // ---------------------------------------------------------------------
        Schema::table('employees', function (Blueprint $table) {
            $table->index(
                ['active', 'is_employee'],
                'idx_employees_active_is_employee'
            );
        });

        // ---------------------------------------------------------------------
        // EMPLOYEES: dailySummary LEFT JOIN ON (e.employee_no = a.UserCode OR e.access_no = a.UserCode)
        // employee_no is already unique (indexed). access_no had unique dropped - add index for join.
        // ---------------------------------------------------------------------
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'access_no')) {
                $table->index('access_no', 'idx_employees_access_no');
            }
        });

        // ---------------------------------------------------------------------
        // DEPARTMENTS: dailySummary LEFT JOIN ... AND d.active = 1
        // ---------------------------------------------------------------------
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'active')) {
                $table->index('active', 'idx_departments_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_active_is_employee');
            if (Schema::hasColumn('employees', 'access_no')) {
                $table->dropIndex('idx_employees_access_no');
            }
        });

        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'active')) {
                $table->dropIndex('idx_departments_active');
            }
        });
    }
};
