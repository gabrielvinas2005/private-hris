<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('applicant_id')->default(0);
            $table->integer('application_status_id');
            $table->integer('position_applied_id');
            $table->boolean('is_plantilla');
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
        Schema::dropIfExists('applicant_details');
    }
}
