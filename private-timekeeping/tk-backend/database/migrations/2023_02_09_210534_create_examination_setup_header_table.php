<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExaminationSetupHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('examination_setup_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exam_set')->unique();
            $table->text('exam_instruction')->nullable();
            $table->decimal('exam_duration', 18, 2)->default(0)->nullable();
            $table->decimal('passing_criteria', 18, 2)->default(0)->nullable();
            $table->boolean('with_video_recording')->default(0)->nullable();
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
        Schema::dropIfExists('examination_setup_header');
    }
}
