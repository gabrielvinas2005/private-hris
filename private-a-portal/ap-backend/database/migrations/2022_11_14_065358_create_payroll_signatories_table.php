<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollSignatoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_signatories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id');
            $table->string('report_name');
            $table->string('signatory_1')->nullable();
            $table->string('signatory_position_1')->nullable();
            $table->string('signatory_2')->nullable();
            $table->string('signatory_position_2')->nullable();
            $table->string('signatory_3')->nullable();
            $table->string('signatory_position_3')->nullable();
            $table->string('signatory_4')->nullable();
            $table->string('signatory_position_4')->nullable();
            $table->string('signatory_label_1')->nullable();
            $table->string('signatory_label_2')->nullable();
            $table->string('signatory_label_3')->nullable();
            $table->string('signatory_label_4')->nullable();
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
        Schema::dropIfExists('payroll_signatories');
    }
}
