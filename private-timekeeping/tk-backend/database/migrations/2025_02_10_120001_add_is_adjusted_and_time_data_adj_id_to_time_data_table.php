<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdjustedAndTimeDataAdjIdToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->boolean('is_adjusted')->default(false);
            $table->unsignedBigInteger('time_data_adj_id')->nullable();
            $table->foreign('time_data_adj_id')->references('id')->on('time_data_adj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->dropForeign(['time_data_adj_id']);
            $table->dropColumn(['is_adjusted', 'time_data_adj_id']);
        });
    }
}
