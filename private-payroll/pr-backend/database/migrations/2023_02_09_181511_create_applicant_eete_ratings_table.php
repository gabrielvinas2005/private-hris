<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantEeteRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_eete_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('applicant_id');
            $table->decimal('education_rating', 18, 2)->default(0)->nullable();
            $table->decimal('experience_rating', 18, 2)->default(0)->nullable();
            $table->decimal('training_rating', 18, 2)->default(0)->nullable();
            $table->decimal('eligibility_rating', 18, 2)->default(0)->nullable();
            $table->integer('reviewed_by')->default(0)->nullable();
            $table->dateTime('reviewed_date')->default(0)->nullable();
            $table->integer('reviewed_status_id')->default(0)->nullable();
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
        Schema::dropIfExists('applicant_eete_ratings');
    }
}
