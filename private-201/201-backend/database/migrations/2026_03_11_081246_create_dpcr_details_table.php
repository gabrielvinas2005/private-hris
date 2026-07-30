<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpcrDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dpcr_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_dpcr_id')->nullable();
            $table->unsignedBigInteger('dpcr_header_id');
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
        Schema::dropIfExists('dpcr_details');
    }
}
