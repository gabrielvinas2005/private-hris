<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeaderIdToYearendBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('yearend_bonus', function (Blueprint $table) {
            $table->integer('yearend_header_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('yearend_bonus', function (Blueprint $table) {
            $table->dropColumn('yearend_header_id');
        });
    }
}
