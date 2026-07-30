<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewApplicantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_applicants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('interview_id');
            $table->integer('applicant_id');
            $table->boolean('is_complete_interview')->default(0)->nullable();
            $table->boolean('is_cancelled_interview')->default(0)->nullable();
            $table->boolean('is_expired_interview')->default(0)->nullable();
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
        Schema::dropIfExists('interview_applicants');
    }
}
