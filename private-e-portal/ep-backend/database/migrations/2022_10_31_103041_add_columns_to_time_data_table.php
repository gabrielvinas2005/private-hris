<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            $table->decimal('late_offset')->default(0);
            $table->decimal('undertime_offset')->default(0);
            $table->decimal('absent_offset')->default(0);
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
            $table->dropColumn('late_offset');
            $table->dropColumn('undertime_offset');
            $table->dropColumn('absent_offset');
        });
    }
}
