<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSssTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sss', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('min_income')->default(0)->nullable();
            $table->decimal('max_income')->default(0)->nullable();
            $table->decimal('ER')->default(0)->nullable();
            $table->decimal('EE')->default(0)->nullable();
            $table->decimal('MPF_ER')->default(0)->nullable();
            $table->decimal('MPF_EE')->default(0)->nullable();
            $table->decimal('WISP')->default(0)->nullable();
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
        Schema::dropIfExists('sss');
    }
}
