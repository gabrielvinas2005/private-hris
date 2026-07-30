<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostedColumnToHazardPayHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hazard_pay_headers', function (Blueprint $table) {
            $table->boolean('posted')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hazard_pay_headers', function (Blueprint $table) {
            $table->dropColumn([
                'posted',
            ]);
        });
    }
}