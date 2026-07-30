<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBiometricLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('biometric_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('access_no')->nullable();
            $table->date('date')->nullable();
            $table->time('am_in')->nullable();
            $table->time('am_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('pm_in')->nullable();
            $table->time('pm_out')->nullable();
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
        Schema::dropIfExists('biometric_logs');
    }
}
