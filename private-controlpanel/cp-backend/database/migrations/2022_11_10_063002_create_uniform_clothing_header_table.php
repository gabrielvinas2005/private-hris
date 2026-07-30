<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniformClothingHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uniform_clothing_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->nullable();
            $table->integer('year')->nullable();
            $table->integer('month_id')->nullable();
            $table->boolean('posted')->default(0)->nullable();
            $table->boolean('active')->default(0)->nullable();
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
        Schema::dropIfExists('uniform_clothing_header');
    }
}
