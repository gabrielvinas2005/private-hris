<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('deduction_id')->default(0)->nullable();
            $table->integer('employee_id')->default(0)->nullable();
            $table->decimal('loan_amount')->default(0)->nullable();
            $table->decimal('loan_amortization')->default(0)->nullable();
            $table->string('remarks')->nullable();
            $table->date('effectivity_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_approve')->default(0)->nullable();
            $table->boolean('is_disapprove')->default(0)->nullable();
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
        Schema::dropIfExists('loan_applications');
    }
}
