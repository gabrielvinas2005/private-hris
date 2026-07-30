<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetainNextMonthToPayrollIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_incomes', 'retain_next_month')) {
                $table->boolean('retain_next_month')->default(false)->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_incomes', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_incomes', 'retain_next_month')) {
                $table->dropColumn('retain_next_month');
            }
        });
    }
}

