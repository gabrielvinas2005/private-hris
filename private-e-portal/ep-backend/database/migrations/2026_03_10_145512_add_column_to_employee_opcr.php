<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToEmployeeOpcr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_opcr', function (Blueprint $table) {
            //
            $table->bigInteger('planning_officer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_opcr', function (Blueprint $table) {
            //
            $table->dropColumn('planning_officer_id');
        });
    }
}
