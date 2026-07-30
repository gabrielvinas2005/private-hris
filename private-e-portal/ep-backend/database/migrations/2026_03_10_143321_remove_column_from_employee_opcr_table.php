<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnFromEmployeeOpcrTable extends Migration
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
            $table->dropColumn('reviewed_by');
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
            $table->string('reviewed_by', 255)->nullable();
        });
    }
}
