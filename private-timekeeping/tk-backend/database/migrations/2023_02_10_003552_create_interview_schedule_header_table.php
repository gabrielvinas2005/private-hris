<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewScheduleHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_schedule_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('interview_set')->unique();
            $table->text('interview_location')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->time('time_start', 0)->nullable();
            $table->time('time_end', 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_schedule_header');
    }
}
