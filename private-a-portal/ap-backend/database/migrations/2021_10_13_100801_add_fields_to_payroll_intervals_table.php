<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPayrollIntervalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_intervals', function (Blueprint $table) {
            $table->integer('day_interval')->default(0)->nullable();
            $table->integer('month_frequency')->default(0)->nullable();
            $table->integer('year_frequency')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_intervals', function (Blueprint $table) {
            $table->dropColumn('day_interval');
            $table->dropColumn('month_frequency');
            $table->dropColumn('year_frequency');
        });
    }
}
