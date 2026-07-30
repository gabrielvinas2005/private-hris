<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingRequisitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_requisition', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('srno')->nullable();
            $table->dateTime('request_date')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->time('start_time', 0);
            $table->time('end_time', 0);
            $table->decimal('hrs_day')->default(0)->nullable();
            $table->decimal('total_hrs')->default(0)->nullable();
            $table->boolean('is_internal')->default(0)->nullable();
            $table->boolean('is_paid')->default(0)->nullable();
            $table->string('training_location')->nullable();
            $table->integer('training_type_id')->nullable();
            $table->string('training_title')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('status')->nullable();
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
        Schema::dropIfExists('training_requisition');
    }
}
