<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDateCompletedToDatetimeInApplicantExaminationHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_examination_headers', function (Blueprint $table) {
            $table->dateTime('date_completed')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_examination_headers', function (Blueprint $table) {
            $table->date('date_completed')->nullable()->change();
        });
    }
}
