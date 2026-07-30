<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsEmergencyLeaveToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->boolean('is_emergency_leave')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->dropColumn('is_emergency_leave');
        });
    }
}
