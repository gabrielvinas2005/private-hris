<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniformClothingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uniform_clothing_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('uniform_clothing_header_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('uniform_clothing_setup_id')->nullable();
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
        Schema::dropIfExists('uniform_clothing_details');
    }
}
