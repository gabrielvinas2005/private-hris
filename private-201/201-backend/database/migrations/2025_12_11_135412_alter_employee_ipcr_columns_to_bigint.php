<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterEmployeeIpcrColumnsToBigint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, clean up the data - set non-numeric values to NULL (as empty string, which converts to NULL)
        // This ensures all remaining values are either NULL or valid numeric strings
        DB::statement("UPDATE employee_ipcr SET reviewed_by = NULL WHERE reviewed_by IS NOT NULL AND reviewed_by != '' AND TRY_CAST(reviewed_by AS BIGINT) IS NULL");
        DB::statement("UPDATE employee_ipcr SET approved_by = NULL WHERE approved_by IS NOT NULL AND approved_by != '' AND TRY_CAST(approved_by AS BIGINT) IS NULL");
        DB::statement("UPDATE employee_ipcr SET assessed_by = NULL WHERE assessed_by IS NOT NULL AND assessed_by != '' AND TRY_CAST(assessed_by AS BIGINT) IS NULL");
        DB::statement("UPDATE employee_ipcr SET final_rater = NULL WHERE final_rater IS NOT NULL AND final_rater != '' AND TRY_CAST(final_rater AS BIGINT) IS NULL");

        // Also set empty strings to NULL
        DB::statement("UPDATE employee_ipcr SET reviewed_by = NULL WHERE reviewed_by = ''");
        DB::statement("UPDATE employee_ipcr SET approved_by = NULL WHERE approved_by = ''");
        DB::statement("UPDATE employee_ipcr SET assessed_by = NULL WHERE assessed_by = ''");
        DB::statement("UPDATE employee_ipcr SET final_rater = NULL WHERE final_rater = ''");

        // Now convert the column types using raw SQL for SQL Server
        // SQL Server can convert numeric strings to BIGINT, and NULL values are fine
        DB::statement("ALTER TABLE employee_ipcr ALTER COLUMN reviewed_by BIGINT NULL");
        DB::statement("ALTER TABLE employee_ipcr ALTER COLUMN approved_by BIGINT NULL");
        DB::statement("ALTER TABLE employee_ipcr ALTER COLUMN assessed_by BIGINT NULL");
        DB::statement("ALTER TABLE employee_ipcr ALTER COLUMN final_rater BIGINT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_ipcr', function (Blueprint $table) {
            // Revert back to nvarchar(255)
            $table->string('reviewed_by', 255)->nullable()->change();
            $table->string('approved_by', 255)->nullable()->change();
            $table->string('assessed_by', 255)->nullable()->change();
            $table->string('final_rater', 255)->nullable()->change();
        });
    }
}
