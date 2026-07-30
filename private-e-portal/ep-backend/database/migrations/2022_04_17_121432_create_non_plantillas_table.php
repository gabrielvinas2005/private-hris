<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonPlantillasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_plantillas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('position_id')->default(0);
            $table->integer('salary_step_id')->default(0);
            $table->integer('salary_grade_id')->default(0);
            $table->decimal('salary')->default(0);
            $table->integer('vacant')->default(0);
            $table->integer('department_id')->default(0);
            $table->string('description')->nullable();
            $table->string('qualification')->nullable();
            $table->string('eligibility')->nullable();
            $table->string('education')->nullable();
            $table->string('experience')->nullable();
            $table->string('training')->nullable();
            $table->date('publication_from')->nullable();
            $table->date('publication_to')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('non_plantillas');
    }
}
