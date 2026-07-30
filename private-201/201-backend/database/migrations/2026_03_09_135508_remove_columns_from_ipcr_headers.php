<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnsFromIpcrHeaders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ipcr_headers', function (Blueprint $table) {
            //
            $table->dropColumn('department_id');
            $table->dropColumn('division_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ipcr_headers', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('division_id')->nullable();
        });
    }
}
