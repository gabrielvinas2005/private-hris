<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnableBiometricToTimeKeepingSetupsTable extends Migration
{
    /**
     * Run the migrations.
     * Adds enable_biometric column to time_keeping_setups table.
     */
    public function up()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            // Whether biometric hardware log-in / punch-in option is enabled for employees
            $table->boolean('enable_biometric')->default(true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            $table->dropColumn('enable_biometric');
        });
    }
}
