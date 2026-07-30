<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantJOTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_jo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_detail_id');
            $table->unsignedBigInteger('applicant_id');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->boolean('is_expired')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();


            // $table->foreign('applicant_detail_id')->references('id')->on('applicant_details')->onDelete('cascade');
            // $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applicant_jo');
    }
}
