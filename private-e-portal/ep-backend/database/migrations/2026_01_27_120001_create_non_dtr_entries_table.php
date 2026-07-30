<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonDtrEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_dtr_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('non_dtr_task_id');
            $table->unsignedBigInteger('payroll_schedule_header_id')->nullable();
            $table->date('work_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->decimal('hours_worked', 5, 2)->default(0);
            $table->text('accomplishments')->nullable(); // Daily accomplishments
            $table->text('output_description')->nullable();
            $table->string('location')->nullable(); // Work location
            $table->timestamps();

            // Foreign key
            $table->foreign('non_dtr_task_id')
                ->references('id')
                ->on('non_dtr_task')
                ->onDelete('cascade');

            // Indexes
            $table->index('non_dtr_task_id');
            $table->index('work_date');
            $table->index('for_payroll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('non_dtr_entries');
    }
}
