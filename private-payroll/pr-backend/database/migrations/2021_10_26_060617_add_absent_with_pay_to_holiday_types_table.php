<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbsentWithPayToHolidayTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('holiday_types', function (Blueprint $table) {
            $table->boolean('absent_with_pay')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('holiday_types', function (Blueprint $table) {
            $table->dropColumn('absent_with_pay');
        });
    }
}
