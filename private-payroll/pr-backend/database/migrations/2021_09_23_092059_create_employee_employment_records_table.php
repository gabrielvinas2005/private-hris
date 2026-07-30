<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeEmploymentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_employment_records', function (Blueprint $table) {
            $table->bigIncrements('employment_record_id');
            $table->integer('employee_id')->default(0);
            $table->date('work_start_date')->nullable();
            $table->date('work_end_date')->nullable();
            $table->string('work_company')->nullable();
            $table->decimal('monthly_salary')->default(0)->nullable();
            $table->string('salary_grade_step')->nullable();
            $table->string('status_of_appointment')->nullable();
            $table->string('position')->nullable();
            $table->integer('government_service_id')->default(0)->nullable();
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
        Schema::dropIfExists('employee_employment_records');
    }
}
