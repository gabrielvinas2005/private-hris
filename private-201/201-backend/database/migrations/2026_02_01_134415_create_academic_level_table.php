<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademicLevelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('academic_level', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->default(DB::raw('getdate()'));
            $table->dateTime('updated_at')->default(DB::raw('getdate()'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('academic_level');
    }
}
