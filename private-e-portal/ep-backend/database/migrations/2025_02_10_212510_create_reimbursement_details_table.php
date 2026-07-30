<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReimbursementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reimbursement_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reimbursement_headers_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('prepaid_invoice_no')->nullable();
            $table->integer('prepaid_amount')->nullable();
            $table->integer('postpaid_invoice_no')->nullable();
            $table->integer('postpaid_amount')->nullable();
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
        Schema::dropIfExists('reimbursement_details');
    }
}
