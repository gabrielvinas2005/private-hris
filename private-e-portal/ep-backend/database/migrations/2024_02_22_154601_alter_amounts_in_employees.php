<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterAmountsInEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE employees ALTER COLUMN salary DECIMAL(18,2);");
        DB::statement("ALTER TABLE employees ALTER COLUMN sss_amount DECIMAL(18,2);");
        DB::statement("ALTER TABLE employees ALTER COLUMN tax_amount DECIMAL(18,2);");
        DB::statement("ALTER TABLE employees ALTER COLUMN pagibig_amount DECIMAL(18,2);");
        DB::statement("ALTER TABLE employees ALTER COLUMN philhealth_amount DECIMAL(18,2);");
        DB::statement("ALTER TABLE employees ALTER COLUMN gsis_amount DECIMAL(18,2);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
