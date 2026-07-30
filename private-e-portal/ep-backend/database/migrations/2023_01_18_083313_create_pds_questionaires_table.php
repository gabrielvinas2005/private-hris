<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdsQuestionairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pds_questionaires', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->text('questions')->nullable();
            $table->boolean('is_yes')->default(0)->nullable();
            $table->boolean('is_no')->default(0)->nullable();
            $table->string('yes_details')->nullable();
            $table->date('date_filed')->nullable();
            $table->string('case_status')->nullable();
            $table->string('que_id')->nullable();
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
        Schema::dropIfExists('pds_questionaires');
    }
}
