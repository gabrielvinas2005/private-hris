<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBaseNetPayToPayrollSummaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_summaries', function (Blueprint $table) {
            $table->decimal('base_net_pay', 18, 2)
                ->default(0)
                ->after('original_netpay'); // or wherever you prefer
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_summaries', function (Blueprint $table) {
             $table->dropColumn('base_net_pay');
        });
    }
}
