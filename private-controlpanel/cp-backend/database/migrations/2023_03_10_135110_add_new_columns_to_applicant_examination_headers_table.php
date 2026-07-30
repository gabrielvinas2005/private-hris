<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToApplicantExaminationHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_examination_headers', function (Blueprint $table) {
            $table->decimal('exam_rating', 18, 2)->default(0)->nullable();
            $table->integer('total_items')->default(0)->nullable();
            $table->integer('total_score')->default(0)->nullable();
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
            $table->dropColumn('exam_rating', 18, 2);
            $table->dropColumn('total_items');
            $table->dropColumn('total_score');
        });
    }
}
