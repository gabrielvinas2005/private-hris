<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantExaminationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_examination_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('applicant_examination_id');
            $table->integer('question_id');
            $table->integer('choice_id');
            $table->integer('correct_answer_id');
            $table->integer('correct');
            $table->integer('wrong');
            $table->integer('unanswered');
            $table->dateTime('date_submitted');
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
        Schema::dropIfExists('applicant_examination_details');
    }
}
