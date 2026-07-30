<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeaderIdToMidyearBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('midyear_bonus', function (Blueprint $table) {
            $table->integer('midyear_header_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('midyear_bonus', function (Blueprint $table) {
            $table->dropColumn('midyear_header_id');
        });
    }
}
