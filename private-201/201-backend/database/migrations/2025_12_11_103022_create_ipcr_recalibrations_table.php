<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpcrRecalibrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipcr_recalibrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_ipcr_output_id');
            $table->unsignedBigInteger('recalibrated_by_employee_id');

            // Recalibration level/type: 'supervisor', 'hr', 'pmt'
            $table->string('recalibration_level', 20); // Using string instead of enum for SQL Server compatibility

            // Supervisor's recalibrated ratings
            $table->integer('quality_rating');
            $table->integer('efficiency_rating');
            $table->integer('timeliness_rating');
            $table->integer('average_rating');

            // Optional: justification/remarks for recalibration
            $table->text('remarks')->nullable();
            $table->text('justification')->nullable();

            // Status tracking
            $table->string('status', 20)->default('draft'); // 'draft', 'submitted', 'approved'

            $table->timestamps();

            // Indexes
            $table->index('employee_ipcr_output_id');
            $table->index('recalibrated_by_employee_id');
            $table->index('recalibration_level');
            $table->index(['employee_ipcr_output_id', 'recalibration_level']);

            // Allow one recalibration per level per output
            $table->unique(['employee_ipcr_output_id', 'recalibration_level'], 'unique_output_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ipcr_recalibrations');
    }
}
