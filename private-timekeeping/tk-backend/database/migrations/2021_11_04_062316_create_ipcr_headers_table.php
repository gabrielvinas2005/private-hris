<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpcrHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipcr_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('semester_id');
            $table->integer('department_id');
            $table->integer('division_id');
            $table->integer('month_from');
            $table->integer('month_to');
            $table->boolean('is_posted')->default(0)->nullable();
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
        Schema::dropIfExists('ipcr_headers');
    }
}
