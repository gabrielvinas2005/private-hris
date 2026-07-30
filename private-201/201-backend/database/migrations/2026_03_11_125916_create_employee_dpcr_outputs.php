<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDpcrOutputs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_dpcr_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_dpcr_id');
            $table->string('Outputs')->nullable();
            $table->string('Target_measures')->nullable();
            $table->decimal('alloted_budget', 10, 2)->nullable();
            $table->string('div_indiv_accountable')->nullable();
            $table->string('actual_accomplishments');
            $table->decimal('quality_rating', 10, 2)->nullable();
            $table->decimal('efficiency_rating', 10, 2)->nullable();
            $table->decimal('effectiveness_rating', 10, 2)->nullable();
            $table->decimal('average_rating', 10, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_dpcr_outputs');
    }
}
