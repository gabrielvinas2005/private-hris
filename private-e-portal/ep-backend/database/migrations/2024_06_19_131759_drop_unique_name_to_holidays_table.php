<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueNameToHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex('positions_name_unique');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_name_unique');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('holidays_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->unique('name', 'holidays_name_unique', '');
        });
    }
}
