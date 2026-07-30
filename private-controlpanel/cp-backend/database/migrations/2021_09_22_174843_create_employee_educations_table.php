<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeEducationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->bigIncrements('education_id');
            $table->integer('employee_id')->default(0);
            $table->integer('academic_level_id')->default(0);
            $table->string('school_name')->nullable();
            $table->string('program')->nullable();
            $table->integer('from')->default(0)->nullable();
            $table->integer('to')->default(0)->nullable();
            $table->integer('graduated_year')->default(0)->nullable();
            $table->decimal('units_earned')->default(0)->nullable();
            $table->string('honors')->nullable();
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
        Schema::dropIfExists('employee_educations');
    }
}
