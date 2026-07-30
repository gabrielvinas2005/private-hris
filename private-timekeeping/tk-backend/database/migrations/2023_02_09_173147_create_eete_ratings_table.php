<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEeteRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eete_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('education_rating', 18, 2)->default(0)->nullable();
            $table->decimal('experience_rating', 18, 2)->default(0)->nullable();
            $table->decimal('training_rating', 18, 2)->default(0)->nullable();
            $table->decimal('eligibility_rating', 18, 2)->default(0)->nullable();
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
        Schema::dropIfExists('eete_ratings');
    }
}
