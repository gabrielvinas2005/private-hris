<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsWfhToShiftSchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->boolean('is_wfh')->default(0);
        });

        // Match fix_schedules_details behavior: default WFH on Fridays for existing rows
        foreach (DB::table('shift_schedules_details')->select('id', 'shift_date')->cursor() as $row) {
            if (empty($row->shift_date)) {
                continue;
            }
            try {
                if (Carbon::parse($row->shift_date)->dayOfWeek === Carbon::FRIDAY) {
                    DB::table('shift_schedules_details')
                        ->where('id', $row->id)
                        ->update(['is_wfh' => 1]);
                }
            } catch (\Throwable $e) {
                // skip invalid dates
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_schedules_details', function (Blueprint $table) {
            $table->dropColumn('is_wfh');
        });
    }
}
