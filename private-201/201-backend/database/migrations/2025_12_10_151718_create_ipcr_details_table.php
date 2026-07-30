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
            $table->id();
            $table->unsignedBigInteger('employee_ipcr_id');
            $table->unsignedBigInteger('ipcr_header_id');
            $table->unsignedBigInteger('status_id');
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
        Schema::dropIfExists('ipcr_details');
    }
}
