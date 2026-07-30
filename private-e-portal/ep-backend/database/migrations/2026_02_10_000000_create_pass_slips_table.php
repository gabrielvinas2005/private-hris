<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassSlipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pass_slips', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->time('time_out')->nullable();
            $table->time('time_in')->nullable();
            $table->text('destination')->nullable();
            $table->text('purpose')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('division_chief')->nullable();
            $table->enum('status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->text('remarks')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('employees')->onDelete('no action');
            
            // Indexes for better query performance
            $table->index('employee_id');
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pass_slips');
    }
}
