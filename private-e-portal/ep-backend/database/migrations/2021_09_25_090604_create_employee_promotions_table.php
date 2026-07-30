<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_promotions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->integer('position_id')->default(0);
            $table->integer('plantilla_id')->default(0);
            $table->boolean('is_plantilla')->default(0);
            $table->boolean('is_teaching')->default(0);
            $table->integer('nature_of_appointment_id')->default(0);
            $table->integer('employment_type_id')->default(0);
            $table->integer('department_id')->default(0);
            $table->integer('branch_id')->default(0);
            $table->integer('payroll_interval_id')->default(0);
            $table->decimal('old_salary')->default(0)->nullable();
            $table->decimal('old_tax_amount')->default(0)->nullable();
            $table->decimal('old_gsis_amount')->default(0)->nullable();
            $table->decimal('old_sss_amount')->default(0)->nullable();
            $table->decimal('old_pagibig_amount')->default(0)->nullable();
            $table->decimal('old_philhealth_amount')->default(0)->nullable();
            $table->decimal('new_salary')->default(0)->nullable();
            $table->decimal('new_tax_amount')->default(0)->nullable();
            $table->decimal('new_gsis_amount')->default(0)->nullable();
            $table->decimal('new_sss_amount')->default(0)->nullable();
            $table->decimal('new_pagibig_amount')->default(0)->nullable();
            $table->decimal('new_philhealth_amount')->default(0)->nullable();
            $table->date('date_position_appointed')->nullable();
            $table->date('date_of_effectivity')->nullable();
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
        Schema::dropIfExists('employee_promotions');
    }
}
