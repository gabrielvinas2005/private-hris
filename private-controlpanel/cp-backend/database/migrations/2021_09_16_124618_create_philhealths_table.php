<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhilhealthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('philhealths', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('year')->unique();
            $table->decimal('multiplier',18,3)->default(0);
            $table->decimal('income_floor',18,3)->default(0);
            $table->decimal('income_ceiling',18,3)->default(0);
            $table->decimal('fix_rate')->default(0);
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
        Schema::dropIfExists('philhealths');
    }
}
