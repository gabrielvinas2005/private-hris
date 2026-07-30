<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanningOfficerDateToEmployeeOpcrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_opcr', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_opcr', 'planning_officer_date')) {
                $table->date('planning_officer_date')->nullable();
            }
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
            if (Schema::hasColumn('employee_opcr', 'planning_officer_date')) {
                $table->dropColumn('planning_officer_date');
            }
        });
    }
}

