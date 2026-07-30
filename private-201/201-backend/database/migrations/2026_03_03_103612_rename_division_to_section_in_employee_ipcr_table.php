<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDivisionToSectionInEmployeeIpcrTable extends Migration
{
    public function up()
    {
        Schema::table('employee_ipcr', function (Blueprint $table) {
            $table->renameColumn('division', 'section');
        });
    }

    public function down()
    {
        Schema::table('employee_ipcr', function (Blueprint $table) {
            $table->renameColumn('section', 'division');
        });
    }
}
