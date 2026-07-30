<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePmtTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pmt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('Employee_no', 50);
            $table->string('Department_no', 50);
            $table->boolean('is_ipcr')->default(false);
            $table->boolean('is_opcr')->default(false);
            $table->boolean('is_dpcr')->default(false);
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
        Schema::dropIfExists('pmt');
    }
}

