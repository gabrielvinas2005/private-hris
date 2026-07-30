<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantExaminationHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_examination_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('applicant_id');
            $table->integer('exam_schedule_id');
            $table->boolean('is_complete')->default(0)->nullable();
            $table->boolean('is_expired')->default(0)->nullable();
            $table->boolean('is_cancelled')->default(0)->nullable();
            $table->date('date_completed')->nullable();
            $table->date('date_cancelled')->nullable();
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
        Schema::dropIfExists('applicant_examination_headers');
    }
}
