<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashTokenColumnToLoyaltyAwardSetupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loyalty_award_setup', function (Blueprint $table) {
            $table->integer('cash_token')->nullable()->after('cash_award');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loyalty_award_setup', function (Blueprint $table) {
            $table->dropColumn('cash_token');
        });
    }
}