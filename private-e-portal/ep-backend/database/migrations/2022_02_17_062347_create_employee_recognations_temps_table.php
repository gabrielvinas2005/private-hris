<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeRecognationsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_recognations_temps', function (Blueprint $table) {
            $table->bigIncrements('recognation_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->string('recognation')->nullable();
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
        Schema::dropIfExists('employee_recognations_temps');
    }
}
