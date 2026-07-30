<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewPanelRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_panel_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('interview_id');
            $table->integer('employee_id');
            $table->integer('applicant_id');
            $table->decimal('bei_rating', 18, 2)->default(0);
            $table->decimal('competency_rating', 18, 2)->default(0);
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
        Schema::dropIfExists('interview_panel_ratings');
    }
}
