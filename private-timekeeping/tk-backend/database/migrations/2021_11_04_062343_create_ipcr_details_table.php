<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpcrDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipcr_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ipcr_header_id');
            $table->integer('employee_id');
            $table->decimal('rating',8,2)->nullable();  
            $table->string('adjectival_rating')->nullable();  
            $table->string('attachment')->nullable();  
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
        Schema::dropIfExists('ipcr_details');
    }
}
