<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSectionDivisionToPmtTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pmt', function (Blueprint $table) {
            //
            $table->bigInteger('division_id')->nullable();
            $table->bigInteger('section_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pmt', function (Blueprint $table) {
            //
            $table->dropColumn('division_id');
            $table->dropColumn('section_id');
        });
    }
}
