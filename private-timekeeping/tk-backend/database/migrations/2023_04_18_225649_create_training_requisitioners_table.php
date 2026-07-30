<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingRequisitionersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_requisitioners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('division_chief_id')->nullable();
            $table->integer('section_chief_id')->nullable();
            $table->integer('hrdd_id')->nullable();
            $table->integer('rd_officer_id')->nullable();
            $table->boolean('is_division_chief')->default(0)->nullable();
            $table->boolean('is_section_chief')->default(0)->nullable();
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
        Schema::dropIfExists('training_requisitioners');
    }
}
