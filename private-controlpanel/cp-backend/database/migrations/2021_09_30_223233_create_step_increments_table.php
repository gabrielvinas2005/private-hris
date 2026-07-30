<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStepIncrementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('step_increments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->date('effectivity_date')->nullable();
            $table->integer('current_salary_grade_id')->default()->nullable();
            $table->integer('current_salary_step_id')->default()->nullable();
            $table->decimal('current_salary')->default()->nullable();
            $table->integer('new_salary_grade_id')->default()->nullable();
            $table->integer('new_salary_step_id')->default()->nullable();
            $table->decimal('new_salary')->default()->nullable();
            $table->decimal('new_tax_amount')->default(0)->nullable();
            $table->decimal('new_gsis_amount')->default(0)->nullable();
            $table->decimal('new_sss_amount')->default(0)->nullable();
            $table->decimal('new_pagibig_amount')->default(0)->nullable();
            $table->decimal('new_philhealth_amount')->default(0)->nullable();
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
        Schema::dropIfExists('step_increments');
    }
}
