<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedReportPathToNonDtrTaskTable extends Migration
{
    public function up()
    {
        Schema::table('non_dtr_task', function (Blueprint $table) {
            if (!Schema::hasColumn('non_dtr_task', 'approved_report_path')) {
                $table->string('approved_report_path', 500)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('non_dtr_task', function (Blueprint $table) {
            if (Schema::hasColumn('non_dtr_task', 'approved_report_path')) {
                $table->dropColumn('approved_report_path');
            }
        });
    }
}
