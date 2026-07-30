<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnStatusIdToEmployeeOffboardingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_offboardings', function (Blueprint $table) {
            $table->unsignedInteger('reactivated_status_id')->after('date_effectivity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_offboardings', function (Blueprint $table) {
            //
        });
    }
}
