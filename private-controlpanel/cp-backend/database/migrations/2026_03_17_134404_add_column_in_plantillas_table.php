<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInPlantillasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plantillas', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('employment_type_Id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plantillas', function (Blueprint $table) {
            //
            $table->dropColumn('employment_type_Id');
        });
    }
}
