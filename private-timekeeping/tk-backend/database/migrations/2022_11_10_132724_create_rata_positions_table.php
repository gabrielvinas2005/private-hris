<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRataPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rata_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('plantilla_id')->default(0)->nullable();
            $table->decimal('ra_amount', 18, 2)->default(0)->nullable();
            $table->decimal('ta_amount', 18, 2)->default(0)->nullable();
            $table->integer('year')->nullable();
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
        Schema::dropIfExists('rata_positions');
    }
}
