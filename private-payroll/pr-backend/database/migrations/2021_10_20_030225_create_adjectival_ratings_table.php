<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdjectivalRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adjectival_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('numerical_rating1', 8, 2);
            $table->decimal('numerical_rating2', 8, 2);
            $table->string('adjectival_rating')->unique();
            $table->boolean('active')->default(1)->nullable();
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
        Schema::dropIfExists('adjectival_ratings');
    }
}
