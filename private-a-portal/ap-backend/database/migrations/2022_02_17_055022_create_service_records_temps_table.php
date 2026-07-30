<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceRecordsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_records_temps', function (Blueprint $table) {
            $table->bigIncrements('service_record_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('designation')->nullable();
            $table->string('employment_type')->nullable();
            $table->decimal('annual_salary')->default(0)->nullable();
            $table->string('place_of_assignment')->nullable();
            $table->decimal('leave_without_pay')->default(0);
            $table->date('separation_date')->nullable();
            $table->string('cause')->nullable();
            $table->string('branch')->nullable();
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
        Schema::dropIfExists('service_records_temps');
    }
}
