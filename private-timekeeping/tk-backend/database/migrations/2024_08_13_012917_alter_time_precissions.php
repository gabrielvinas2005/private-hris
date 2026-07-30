<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterTimePrecissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            ALTER TABLE dbo.overtime_types
            ALTER COLUMN nd_from TIME(0);

            ALTER TABLE dbo.overtime_types
            ALTER COLUMN nd_to TIME(0);

            ALTER TABLE dbo.examination_schedule_header
            ALTER COLUMN exam_time_from TIME(0);

            ALTER TABLE dbo.examination_schedule_header
            ALTER COLUMN exam_time_to TIME(0);

            ALTER TABLE dbo.interview_schedule_header
            ALTER COLUMN time_start TIME(0);

            ALTER TABLE dbo.interview_schedule_header
            ALTER COLUMN time_end TIME(0);
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
