<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeOpcrOutputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_opcr_outputs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_opcr_id');
            $table->string('category', 100)->nullable(); // Strategy Priority, Support Functions
            $table->text('mfo_pap')->nullable(); // Major Final Output/Program Activity Project
            $table->text('success_indicators')->nullable(); // Success Indicators (Targets + Measures)
            $table->decimal('allotted_budget', 15, 2)->nullable();
            $table->text('division_individuals_accountable')->nullable();
            $table->text('actual_accomplishments')->nullable();
            $table->decimal('quality_rating', 5, 2)->nullable(); // Q
            $table->decimal('efficiency_rating', 5, 2)->nullable(); // E
            $table->decimal('timeliness_rating', 5, 2)->nullable(); // T
            $table->decimal('average_rating', 5, 2)->nullable(); // A
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_opcr_id')->references('id')->on('employee_opcr')->onDelete('cascade');

            // Indexes
            $table->index('employee_opcr_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_opcr_outputs');
    }
}

