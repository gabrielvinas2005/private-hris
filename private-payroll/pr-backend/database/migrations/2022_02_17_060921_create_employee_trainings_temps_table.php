<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTrainingsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_trainings_temps', function (Blueprint $table) {
            $table->bigIncrements('training_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->string('training')->nullable();
            $table->date('training_from')->nullable();
            $table->date('training_to')->nullable();
            $table->decimal('hours')->default(0)->nullable();
            $table->string('sponsored_by')->nullable();
            $table->integer('learning_id')->default(0)->nullable();
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
        Schema::dropIfExists('employee_trainings_temps');
    }
}
