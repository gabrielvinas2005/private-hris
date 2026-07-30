<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeExaminationsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_examinations_temps', function (Blueprint $table) {
            $table->bigIncrements('examination_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->decimal('exam_rating')->default(0);
            $table->date('exam_date')->nullable();
            $table->string('place_of_exam')->nullable();
            $table->string('license_number')->nullable();
            $table->date('date_released')->nullable();
            $table->integer('eligibility_id')->default(0)->nullable();
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
        Schema::dropIfExists('employee_examinations_temps');
    }
}
