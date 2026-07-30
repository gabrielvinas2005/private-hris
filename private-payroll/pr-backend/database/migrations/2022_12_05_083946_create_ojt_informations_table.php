<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOjtInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ojt_informations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 3000)->unique();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->decimal('hours', 18, 2)->default(0)->nullable();
            $table->string('signatory_name', 3000)->nullable();
            $table->string('signatory_position', 3000)->nullable();
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
        Schema::dropIfExists('ojt_informations');
    }
}
