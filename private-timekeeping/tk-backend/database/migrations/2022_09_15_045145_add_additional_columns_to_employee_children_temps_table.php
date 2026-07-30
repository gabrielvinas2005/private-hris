<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToEmployeeChildrenTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_children_temps', function (Blueprint $table) {
            $table->string('child_middlename')->nullable();
            $table->string('child_lastname')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_children_temps', function (Blueprint $table) {
            $table->dropColumn('child_middlename');
            $table->dropColumn('child_lastname');
        });
    }
}
