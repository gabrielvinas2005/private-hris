<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedDtrPathToTimeDataRequestTable extends Migration
{
    public function up()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            if (!Schema::hasColumn('time_data_request', 'approved_dtr_path')) {
                $table->string('approved_dtr_path', 500)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            if (Schema::hasColumn('time_data_request', 'approved_dtr_path')) {
                $table->dropColumn('approved_dtr_path');
            }
        });
    }
}
