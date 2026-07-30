<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsWfhToFixSchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fix_schedules_details', function (Blueprint $table) {
            $table->boolean('is_wfh')->default(0);
        });

        // Default WFH on Friday (schedule_days.id = 5) for existing rows
        DB::table('fix_schedules_details')
            ->where('day_id', 5)
            ->update(['is_wfh' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fix_schedules_details', function (Blueprint $table) {
            $table->dropColumn('is_wfh');
        });
    }
}
