<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnsFromOpcrHeader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opcr_headers', function (Blueprint $table) {
            //
            $table->dropColumn('department_id');
            $table->dropColumn('section_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opcr_header', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('section_id');
        });
    }
}
