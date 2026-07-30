<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeSkillsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_skills_temps', function (Blueprint $table) {
            $table->bigIncrements('skill_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->string('skill')->nullable();
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
        Schema::dropIfExists('employee_skills_temps');
    }
}
