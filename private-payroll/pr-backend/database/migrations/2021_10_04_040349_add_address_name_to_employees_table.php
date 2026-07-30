<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressNameToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('ra_region_name')->nullable();
            $table->string('ra_province_name')->nullable();
            $table->string('ra_city_name')->nullable();
            $table->string('pa_region_name')->nullable();
            $table->string('pa_province_name')->nullable();
            $table->string('pa_city_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('ra_region_name');
            $table->dropColumn('ra_province_name');
            $table->dropColumn('ra_city_name');
            $table->dropColumn('pa_region_name');
            $table->dropColumn('pa_province_name');
            $table->dropColumn('pa_city_name');
        });
    }
}
