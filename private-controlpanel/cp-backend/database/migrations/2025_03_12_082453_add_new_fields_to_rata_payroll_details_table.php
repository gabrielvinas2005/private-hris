<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToRataPayrollDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rata_payroll_details', function (Blueprint $table) {
            $table->decimal('no_of_days_absent', 18, 2)->default(0);
            $table->decimal('rata_percentage', 18, 2)->default(0);
            $table->decimal('use_vehicle_rp_ta', 18, 2)->default(0);
            $table->decimal('total_deduction', 18, 2)->default(0);
            $table->decimal('amount_earned', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rata_payroll_details', function (Blueprint $table) {
            $table->dropColumn([
                'no_of_days_absent',
                'rata_percentage',
                'use_vehicle_rp_ta',
                'total_deduction',
                'amount_earned',
                'net_amount',
            ]);
        });
    }
}
