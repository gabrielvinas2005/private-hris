<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFiscalYearToOvertimeTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_taxes', function (Blueprint $table) {
            $table->integer('fiscal_year')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_taxes', function (Blueprint $table) {
            $table->dropColumn('fiscal_year');
        });
    }
}
