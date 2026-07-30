<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkSpecializationIdToEmployeeEmploymentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_employment_records', function (Blueprint $table) {
            $table->integer('work_specialization_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_employment_records', function (Blueprint $table) {
            $table->dropColumn('work_specialization_id');
        });
    }
}
