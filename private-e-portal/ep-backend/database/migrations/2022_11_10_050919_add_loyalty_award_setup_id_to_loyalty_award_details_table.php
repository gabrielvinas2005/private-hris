<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoyaltyAwardSetupIdToLoyaltyAwardDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loyalty_award_details', function (Blueprint $table) {
            $table->integer('loyalty_award_setup_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loyalty_award_details', function (Blueprint $table) {
            $table->dropColumn('loyalty_award_setup_id');
        });
    }
}
