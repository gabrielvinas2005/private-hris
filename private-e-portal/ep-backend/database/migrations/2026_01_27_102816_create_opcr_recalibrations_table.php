<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpcrRecalibrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opcr_recalibrations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_opcr_output_id');
            $table->bigInteger('recalibrated_by_employee_id');
            $table->string('recalibration_level');
            $table->decimal('quality_rating', 5, 2);
            $table->decimal('efficiency_rating', 5, 2);
            $table->decimal('timeliness_rating', 5, 2);
            $table->decimal('average_rating', 5, 2);
            $table->text('remarks');
            $table->string('status');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opcr_recalibrations');
    }
}
