<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOvertimeTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_taxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('amount_from', 18, 2)->default(0)->nullable();
            $table->decimal('amount_to', 18, 2)->default(0)->nullable();
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
        Schema::dropIfExists('overtime_taxes');
    }
}
