<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnswerTextToApplicantExaminationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_examination_details', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('choice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_examination_details', function (Blueprint $table) {
            $table->dropColumn('answer_text');
        });
    }
}
