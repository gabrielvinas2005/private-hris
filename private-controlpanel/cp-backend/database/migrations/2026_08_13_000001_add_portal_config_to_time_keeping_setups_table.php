<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortalConfigToTimeKeepingSetupsTable extends Migration
{
    /**
     * Run the migrations.
     * Adds Employee Portal feature-flag columns to the timekeeping setup table.
     * These columns allow HR admins to configure Time & Attendance page behaviour
     * per employment type directly from the Control Panel.
     */
    public function up()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            // Maximum number of pass slips an employee may file per calendar month
            $table->integer('pass_slip_monthly_limit')->default(4)->nullable();

            // Whether the web clock-in/out terminal is available to employees
            $table->boolean('enable_web_clock')->default(true)->nullable();

            // Whether a selfie photo is required when web-clocking
            $table->boolean('require_selfie')->default(true)->nullable();

            // Whether GPS geofence must be within the allowed radius for web clock
            $table->boolean('enforce_geofence')->default(true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('time_keeping_setups', function (Blueprint $table) {
            $table->dropColumn([
                'pass_slip_monthly_limit',
                'enable_web_clock',
                'require_selfie',
                'enforce_geofence',
            ]);
        });
    }
}
