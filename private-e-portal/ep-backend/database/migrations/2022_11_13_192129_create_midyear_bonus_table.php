<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMidyearBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('midyear_bonus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('years')->nullable();
            $table->decimal('salary', 18, 2)->default(0)->nullable();
            $table->decimal('bonus_amount', 18, 2)->default(0)->nullable();
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
        Schema::dropIfExists('midyear_bonus');
    }
}
