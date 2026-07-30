<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultipleColumnsToLoyaltyAwardSignatoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loyalty_award_signatories', function (Blueprint $table) {
            $table->string('signatory_5')->nullable();
            $table->string('signatory_position_5')->nullable();
            $table->string('description_5')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loyalty_award_signatories', function (Blueprint $table) {
            Schema::dropIfExists('loyalty_award_signatories');
        });
    }
}