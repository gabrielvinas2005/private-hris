<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtraBonusPayrollDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extra_bonus_payroll_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('extra_bonus_id');
            $table->integer('employee_id');
            $table->decimal('amount', 18, 2)->default(0);
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
        Schema::dropIfExists('extra_bonus_payroll_details');
    }
}
