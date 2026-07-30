<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantInterviewHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_interview_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('panel_group');
            $table->string('interview_location');
            $table->text('description')->nullable();
            $table->integer('panel_group_level');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time', 0);
            $table->time('end_time', 0);
            $table->boolean('posted')->default(0)->nullable();
            $table->integer('posted_by')->default(0)->nullable();
            $table->dateTime('posted_date')->nullable();
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
        Schema::dropIfExists('applicant_interview_headers');
    }
}
