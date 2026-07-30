<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYearendBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yearend_bonus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('years')->nullable();
            $table->decimal('salary', 18, 2)->default(0)->nullable();
            $table->decimal('bonus_amount', 18, 2)->default(0)->nullable();
            $table->decimal('cash_gift_incentive', 18, 2)->default(0)->nullable();
            $table->decimal('cash_gift_amount', 18, 2)->default(0)->nullable();
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
        Schema::dropIfExists('yearend_bonus');
    }
}
