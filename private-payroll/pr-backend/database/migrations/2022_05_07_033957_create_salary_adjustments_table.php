<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id');
            $table->decimal('old_salary')->nullable();
            $table->integer('old_salary_grade_id')->nullable();
            $table->integer('old_salary_step_id')->nullable();
            $table->decimal('new_salary');
            $table->integer('new_salary_grade_id');
            $table->integer('new_salary_step_id');
            $table->integer('salary_schedule_id');
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
        Schema::dropIfExists('salary_adjustments');
    }
}
