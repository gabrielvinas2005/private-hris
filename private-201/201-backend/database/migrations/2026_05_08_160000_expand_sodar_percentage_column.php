<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandSodarPercentageColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("ALTER TABLE [SODAR] ALTER COLUMN [Percetage] NVARCHAR(50) NOT NULL");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "SODAR" ALTER COLUMN "Percetage" TYPE VARCHAR(50)');
            DB::statement('ALTER TABLE "SODAR" ALTER COLUMN "Percetage" SET NOT NULL');
            return;
        }

        // Fallback for other drivers used in local/dev environments.
        DB::statement("ALTER TABLE SODAR MODIFY Percetage VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Keep as no-op because previous production type is not reliably known.
    }
}

