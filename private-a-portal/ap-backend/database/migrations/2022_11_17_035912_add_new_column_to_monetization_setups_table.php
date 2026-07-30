<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToMonetizationSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monetization_setups', function (Blueprint $table) {
            $table->integer('maximum_number_allowed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monetization_setups', function (Blueprint $table) {
            $table->dropColumn('maximum_number_allowed');
        });
    }
}
