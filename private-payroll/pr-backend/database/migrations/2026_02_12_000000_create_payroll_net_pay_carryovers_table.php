<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollNetPayCarryoversTable extends Migration
{
    /**
     * Run the migrations.
     * Stores fractional cents of net pay from 1st half to add to 2nd half (per employee per month).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_net_pay_carryovers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('carried_amount', 18, 4)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'month']);
            $table->index(['employee_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_net_pay_carryovers');
    }
}
