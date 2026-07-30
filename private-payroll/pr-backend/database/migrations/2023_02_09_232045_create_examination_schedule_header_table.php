<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExaminationScheduleHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('examination_schedule_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('exam_id');
            $table->date('exam_date_from')->nullable();
            $table->date('exam_date_to')->nullable();
            $table->boolean('posted')->default(0)->nullable();
            $table->integer('posted_by')->default(0)->nullable();
            $table->dateTime('posted_date')->nullable();
            $table->boolean('is_expired')->default(0)->nullable();
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
        Schema::dropIfExists('examination_schedule_header');
    }
}
