<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpcrDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opcr_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_opcr_id')->nullable();
            $table->unsignedBigInteger('opcr_header_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opcr_details');
    }
}
