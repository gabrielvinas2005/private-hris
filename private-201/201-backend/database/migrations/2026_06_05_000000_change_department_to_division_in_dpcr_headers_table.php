<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDepartmentToDivisionInDpcrHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dpcr_headers', function (Blueprint $table) {
            $table->renameColumn('department_id', 'division_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dpcr_headers', function (Blueprint $table) {
            $table->renameColumn('division_id', 'department_id');
        });
    }
}
