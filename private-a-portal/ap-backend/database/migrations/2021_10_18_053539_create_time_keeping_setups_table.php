<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeKeepingSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_keeping_setups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employment_type_id');
            $table->decimal('work_days')->default(0)->nullable();
            $table->decimal('work_hours')->default(0)->nullable();
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
        Schema::dropIfExists('timkeeping_setups');
    }
}
