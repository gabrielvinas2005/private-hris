<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpcrRecalibrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dpcr_recalibrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_dpcr_id');
            $table->unsignedBigInteger('recalibrated_by_employee_id');
            $table->string('recalibration_level');
            $table->decimal('quality_rating', 10, 2)->nullable();
            $table->decimal('efficiency_rating', 10, 2)->nullable();
            $table->decimal('effectiveness_rating', 10, 2)->nullable();
            $table->decimal('average_rating', 10, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('status');
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
        Schema::dropIfExists('dpcr_recalibrations');
    }
}
