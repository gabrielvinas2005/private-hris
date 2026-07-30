<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToApplicantExaminationDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_examination_details', function (Blueprint $table) {
            $table->integer('essay_score')->nullable()->after('is_complete');
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
            $table->dropColumn('essay_score');
        });
    }
}
