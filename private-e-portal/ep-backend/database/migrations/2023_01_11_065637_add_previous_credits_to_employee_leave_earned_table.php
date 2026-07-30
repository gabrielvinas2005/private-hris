<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviousCreditsToEmployeeLeaveEarnedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_leave_earned', function (Blueprint $table) {
            $table->decimal('previous_vl_credits', 18, 3)->default(0)->nullable();
            $table->decimal('previous_sl_credits', 18, 3)->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_leave_earned', function (Blueprint $table) {
            $table->dropColumn('previous_vl_credits');
            $table->dropColumn('previous_sl_credits');
        });
    }
}
