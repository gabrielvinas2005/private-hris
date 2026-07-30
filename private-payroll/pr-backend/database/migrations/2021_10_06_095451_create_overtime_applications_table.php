<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOvertimeApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->integer('overtime_type_id')->default(0);
            $table->dateTime('date')->nullable();
            $table->time('date_time_from', 0)->nullable();
            $table->time('date_time_to', 0)->nullable();
            $table->decimal('total_hours')->default(0)->nullable();
            $table->boolean('service_credits')->default(0);
            $table->boolean('payroll')->default(0);
            $table->string('remarks')->nullable();
            $table->boolean('approved')->default(0)->nullable();
            $table->boolean('disapproved')->default(0)->nullable();
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
        Schema::dropIfExists('overtime_applications');
    }
}
