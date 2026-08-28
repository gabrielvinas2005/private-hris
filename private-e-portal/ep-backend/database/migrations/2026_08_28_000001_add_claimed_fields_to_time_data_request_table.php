<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClaimedFieldsToTimeDataRequestTable extends Migration
{
    public function up()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            if (!Schema::hasColumn('time_data_request', 'target_date')) {
                $table->date('target_date')->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'field_type')) {
                $table->string('field_type', 50)->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'claimed_time')) {
                $table->string('claimed_time', 20)->nullable();
            }
            if (!Schema::hasColumn('time_data_request', 'reason')) {
                $table->text('reason')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            $columns = ['target_date', 'field_type', 'claimed_time', 'reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('time_data_request', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
