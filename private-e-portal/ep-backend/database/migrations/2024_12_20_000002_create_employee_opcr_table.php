<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeOpcrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_opcr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->string('division', 255)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('reviewed_by', 255)->nullable();
            $table->unsignedBigInteger('reviewed_by_employee_id')->nullable();
            $table->date('reviewed_date')->nullable();
            $table->string('approved_by', 255)->nullable();
            $table->unsignedBigInteger('approved_by_employee_id')->nullable();
            $table->date('approved_date')->nullable();
            $table->string('assessed_by', 255)->nullable();
            $table->unsignedBigInteger('assessed_by_employee_id')->nullable();
            $table->date('assessed_date')->nullable();
            $table->string('final_rater', 255)->nullable();
            $table->unsignedBigInteger('final_rater_employee_id')->nullable();
            $table->date('final_rate_date')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Indexes
            $table->index('employee_id');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_opcr');
    }
}

