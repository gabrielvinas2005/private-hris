<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToNonPlantillasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('non_plantillas', function (Blueprint $table) {
            $table->integer('employee_type_id')->nullable()->default(0);
            $table->integer('number_of_months')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('non_plantillas', function (Blueprint $table) {
            $table->dropColumn('employee_type_id');
            $table->dropColumn('number_of_months');
        });
    }
}
