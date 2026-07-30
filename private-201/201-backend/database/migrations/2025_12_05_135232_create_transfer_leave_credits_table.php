<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferLeaveCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_leave_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('vacation_leave_credits', 10, 2)->default(0);
            $table->decimal('sick_leave_credits', 10, 2)->default(0);
            $table->unsignedBigInteger('transfer_to_employee_id')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            $table->foreign('transfer_to_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_leave_credits');
    }
}
