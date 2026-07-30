<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterTimeColumnsToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE time_data ALTER COLUMN am_in time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN am_out time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN break_in time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN break_out time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN pm_in time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN pm_out time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN nd_start time(0);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN nd_end time(0);");

        DB::statement("ALTER TABLE time_data ALTER COLUMN late decimal(18,3);");
        DB::statement("ALTER TABLE time_data ALTER COLUMN undertime decimal(18,3);");

        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN am_in time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN am_out time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN break_in time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN break_out time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN pm_in time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN pm_out time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN nd_start time(0);");
        DB::statement("ALTER TABLE fix_schedules_details ALTER COLUMN nd_end time(0);");

        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN am_in time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN am_out time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN break_in time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN break_out time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN pm_in time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN pm_out time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN nd_start time(0);");
        DB::statement("ALTER TABLE shift_schedules_details ALTER COLUMN nd_end time(0);");

        DB::statement("ALTER TABLE work_cancellations ALTER COLUMN  time_from time(0);");
        DB::statement("ALTER TABLE work_cancellations ALTER COLUMN  time_to time(0);");

        DB::statement("ALTER TABLE overtime_applications ALTER COLUMN  date_time_from time(0);");
        DB::statement("ALTER TABLE overtime_applications ALTER COLUMN  date_time_to time(0);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data', function (Blueprint $table) {
            //
        });
    }
}
