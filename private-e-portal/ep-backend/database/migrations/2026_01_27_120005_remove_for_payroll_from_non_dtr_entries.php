<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveForPayrollFromNonDtrEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('non_dtr_entries', function (Blueprint $table) {
            // Drop index first if it exists
            $table->dropIndex(['for_payroll']);
        });
        
        Schema::table('non_dtr_entries', function (Blueprint $table) {
            $table->dropColumn('for_payroll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('non_dtr_entries', function (Blueprint $table) {
            $table->boolean('for_payroll')->default(false);
        });
    }
}
