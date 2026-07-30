<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalarySchedulesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_schedules_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('salary_schedule_id')->default(0);
            $table->integer('salary_grade_id')->default(0);
            $table->integer('salary_step_id')->default(0);
            $table->decimal('amount')->default(0);
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
        Schema::dropIfExists('salary_schedules_details');
    }
}
