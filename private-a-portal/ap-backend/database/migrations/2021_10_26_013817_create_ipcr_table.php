<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpcrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipcr', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->nullable();
            $table->decimal('numerical_rating',8,2);
            $table->string('adjectival_rating');  
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->integer('year')->nullable();
            $table->string('progress');
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
        Schema::dropIfExists('ipcr');
    }
}
