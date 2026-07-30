<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollItemScheduleHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_item_schedule_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('payroll_interval_type_id');
            $table->integer('payroll_period_type_id');
            $table->integer('employment_type_id');
            $table->boolean('sss')->default(0)->nullable();
            $table->boolean('pagibig')->default(0)->nullable();
            $table->boolean('philhealth')->default(0)->nullable();
            $table->boolean('gsis')->default(0)->nullable();
            $table->boolean('tax')->default(0)->nullable();
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
        Schema::dropIfExists('payroll_item_schedule_headers');
    }
}
