<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeIpcrOutputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_ipcr_outputs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_ipcr_id');
            $table->text('output')->nullable();
            $table->text('success_indicators')->nullable();
            $table->text('accomplishment')->nullable();
            $table->integer('quality_rating')->default(1);
            $table->integer('efficiency_rating')->default(1);
            $table->integer('timeliness_rating')->default(1);
            $table->integer('average_rating')->default(1);
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_ipcr_id')->references('id')->on('employee_ipcr')->onDelete('cascade');
            
            // Indexes
            $table->index('employee_ipcr_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_ipcr_outputs');
    }
}

