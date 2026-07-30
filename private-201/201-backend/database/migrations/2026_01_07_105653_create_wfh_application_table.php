<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfhApplicationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wfh_application', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason');
            $table->boolean('approved')->default(false);
            $table->boolean('disapproved')->default(false);
            $table->string('disapproved_reason')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->boolean('approved_2')->default(false);
            $table->boolean('disapproved_2')->default(false);
            $table->string('disapproved_reason_2')->nullable();
            $table->dateTime('processed_at_2')->nullable();
            $table->boolean('approved_3')->default(false);
            $table->boolean('disapproved_3')->default(false);
            $table->string('disapproved_reason_3')->nullable();
            $table->dateTime('processed_at_3')->nullable();
            $table->boolean('cancelled')->default(false);
            $table->string('cancelled_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('attachment')->nullable();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wfh_application');
    }
}
