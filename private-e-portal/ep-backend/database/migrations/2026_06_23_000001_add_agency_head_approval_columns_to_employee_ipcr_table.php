<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgencyHeadApprovalColumnsToEmployeeIpcrTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employee_ipcr')) {
            return;
        }

        Schema::table('employee_ipcr', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_ipcr', 'agency_head_approved_by')) {
                $table->unsignedBigInteger('agency_head_approved_by')->nullable()->after('approved_date');
            }
            if (!Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                $table->dateTime('agency_head_approved_at')->nullable()->after('agency_head_approved_by');
            }
            if (!Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                $table->text('agency_head_approval_remarks')->nullable()->after('agency_head_approved_at');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('employee_ipcr')) {
            return;
        }

        Schema::table('employee_ipcr', function (Blueprint $table) {
            foreach (['agency_head_approval_remarks', 'agency_head_approved_at', 'agency_head_approved_by'] as $column) {
                if (Schema::hasColumn('employee_ipcr', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
