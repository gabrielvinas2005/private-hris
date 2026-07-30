<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRataTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rata_tables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('day_from')->default(0)->nullable();
            $table->integer('day_to')->default(0)->nullable();
            $table->decimal('percentage', 18, 2)->default(0)->nullable();
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
        Schema::dropIfExists('rata_tables');
    }
}
