<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForPayrollToNonDtrTask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('non_dtr_task', function (Blueprint $table) {
            $table->boolean('for_payroll')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('non_dtr_task', function (Blueprint $table) {
            $table->dropColumn('for_payroll');
        });
    }
}
