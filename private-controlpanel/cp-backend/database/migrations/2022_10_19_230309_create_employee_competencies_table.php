<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCompetenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_competencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('plantilla_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->integer('subcompetency_id')->default(0);
            $table->integer('level_attained')->default(0);
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
        Schema::dropIfExists('employee_competencies');
    }
}
