<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPendingAmountPaymentToPayrollSummariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_summaries', function (Blueprint $table) {
            $table->decimal('pending_amount_payment', 18, 2)->default(0)->nullable();
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
            $table->dropColumn('pending_amount_payment');
        });
    }
}
