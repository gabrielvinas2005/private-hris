<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToExaminationScheduleHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examination_schedule_header', function (Blueprint $table) {
            $table->time('exam_time_from')->nullable();
            $table->time('exam_time_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('examination_schedule_header', function (Blueprint $table) {
            $table->dropColumn('exam_time_from');
            $table->dropColumn('exam_time_to');
        });
    }
}
