<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_tables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('percentage')->default(0);
            $table->decimal('min_amount')->default(0);
            $table->decimal('max_amount')->default(0);
            $table->decimal('base_tax')->default(0);
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
        Schema::dropIfExists('tax_tables');
    }
}
