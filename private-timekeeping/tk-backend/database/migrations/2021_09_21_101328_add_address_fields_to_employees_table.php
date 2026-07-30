<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressFieldsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('ra_region')->nullable();
            $table->string('ra_province')->nullable();
            $table->string('ra_city')->nullable();
            $table->string('pa_region')->nullable();
            $table->string('pa_province')->nullable();
            $table->string('pa_city')->nullable();
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
            $table->dropColumn('ra_region');
            $table->dropColumn('ra_province');
            $table->dropColumn('ra_city');
            $table->dropColumn('pa_region');
            $table->dropColumn('pa_province');
            $table->dropColumn('pa_city');
        });
    }
}
