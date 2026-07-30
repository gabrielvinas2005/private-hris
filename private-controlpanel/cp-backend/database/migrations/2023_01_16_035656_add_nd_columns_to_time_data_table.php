<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNdColumnsToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->decimal('nd_hours', 18, 2)->default(0)->nullable();
            $table->decimal('nd_rate', 18, 2)->default(0)->nullable();
            $table->time('nd_start')->nullable();
            $table->time('nd_end')->nullable();
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
            $table->dropColumn('nd_hours');
            $table->dropColumn('nd_rate');
            $table->dropColumn('nd_start');
            $table->dropColumn('nd_end');
        });
    }
}
