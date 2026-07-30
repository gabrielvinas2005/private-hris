<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonetizationPayrollHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monetization_payroll_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->default(0)->nullable();
            $table->integer('year_id')->default(0)->nullable();
            $table->integer('month_id')->default(0)->nullable();
            $table->string('month')->nullable();
            $table->boolean('posted')->default(0)->nullable();
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
        Schema::dropIfExists('monetization_payroll_headers');
    }
}
