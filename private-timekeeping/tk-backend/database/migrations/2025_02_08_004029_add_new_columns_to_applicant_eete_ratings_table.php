<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToApplicantEeteRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->boolean('is_education_passed')->default(false);
            $table->boolean('is_experience_passed')->default(false);
            $table->boolean('is_training_passed')->default(false);
            $table->boolean('is_eligibility_passed')->default(false);
            $table->text('interview')->nullable();
            $table->text('bonus')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->dropColumn([
                'is_education_passed',
                'is_experience_passed',
                'is_training_passed',
                'is_eligibility_passed',
                'interview',
                'bonus',
            ]);
        });
    }
}
