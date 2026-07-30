<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToTimeDataRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            $table->boolean('approved_1')->default(0)->nullable();
            $table->integer('approved_by_1_id')->default(0)->nullable();
            $table->dateTime('approved_date_1')->nullable();
            $table->boolean('disapproved_1')->default(0)->nullable();
            $table->integer('disapproved_by_1_id')->default(0)->nullable();
            $table->dateTime('disapproved_date_1')->nullable();
            $table->boolean('approved_2')->default(0)->nullable();
            $table->integer('approved_by_2_id')->default(0)->nullable();
            $table->dateTime('approved_date_2')->nullable();
            $table->boolean('disapproved_2')->default(0)->nullable();
            $table->integer('disapproved_by_2_id')->default(0)->nullable();
            $table->dateTime('disapproved_date_2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data_request', function (Blueprint $table) {
            //
        });
    }
}
