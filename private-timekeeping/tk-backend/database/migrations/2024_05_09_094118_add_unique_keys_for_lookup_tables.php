<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUniqueKeysForLookupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
        IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'branchID' AND Object_ID = Object_ID(N'branches'))
            BEGIN
                ALTER TABLE dbo.branches ADD branchID VARCHAR(20) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'departments'))
            BEGIN
                ALTER TABLE dbo.departments ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'name_prefixes'))
            BEGIN
                ALTER TABLE dbo.name_prefixes ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'civil_status'))
            BEGIN
                ALTER TABLE dbo.civil_status ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'citizenships'))
            BEGIN
                ALTER TABLE dbo.citizenships ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'blood_types'))
            BEGIN
                ALTER TABLE dbo.blood_types ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'employment_types'))
            BEGIN
                ALTER TABLE dbo.employment_types ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'positions'))
            BEGIN
                ALTER TABLE dbo.positions ADD code VARCHAR(15) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'plantillas'))
            BEGIN
                ALTER TABLE dbo.plantillas ADD code VARCHAR(20) NULL
            END

            IF NOT EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'code' AND Object_ID = Object_ID(N'code'))
            BEGIN
                ALTER TABLE dbo.learnings ADD code VARCHAR(20) NULL
            END
        ");
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
