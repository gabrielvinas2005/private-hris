<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamQuestionaireDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_questionaire_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('question_id');
            $table->text('choice_details')->nullable();
            $table->string('choice_image')->nullable();
            $table->text('choice_image_path')->nullable();
            $table->boolean('is_correct_answer')->default(0)->nullable();
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
        Schema::dropIfExists('exam_questionaire_details');
    }
}
