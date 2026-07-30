<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHazardPayDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hazard_pay_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('hazard_pay_id')->default(0)->nullable();
            $table->integer('hazard_pay_setup_id')->nullable();
            $table->integer('employee_id')->default(0)->nullable();
            $table->integer('position_id')->nullable();
            $table->integer('salary_grade_id')->default(0)->nullable();
            $table->integer('salary_step_id')->default(0)->nullable();
            $table->integer('no_of_days')->default(0)->nullable();
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
        Schema::table('hazard_pay_details', function (Blueprint $table) {
            $table->dropColumn([
                'id',
                'hazard_pay_id',
                'hazard_pay_setup_id',
                'employee_id',
                'position_id',
                'salary_grade_id',
                'salary_step_id',
                'no_of_days'
            ]);
        });
    }
}
