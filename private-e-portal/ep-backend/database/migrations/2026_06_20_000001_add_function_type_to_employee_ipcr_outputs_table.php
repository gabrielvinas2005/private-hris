<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFunctionTypeToEmployeeIpcrOutputsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employee_ipcr_outputs')) {
            return;
        }

        Schema::table('employee_ipcr_outputs', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_ipcr_outputs', 'function_type')) {
                $table->string('function_type', 20)->default('core')->after('employee_ipcr_id');
                $table->index('function_type');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('employee_ipcr_outputs')) {
            return;
        }

        Schema::table('employee_ipcr_outputs', function (Blueprint $table) {
            if (Schema::hasColumn('employee_ipcr_outputs', 'function_type')) {
                $table->dropColumn('function_type');
            }
        });
    }
}
