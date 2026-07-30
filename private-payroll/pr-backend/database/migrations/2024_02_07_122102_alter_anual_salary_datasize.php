<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterAnualSalaryDatasize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE service_records ALTER COLUMN annual_salary DECIMAL(18,2);");
        DB::statement("ALTER TABLE employee_employment_records ALTER COLUMN monthly_salary DECIMAL(18,2);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
