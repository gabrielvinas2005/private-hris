<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DropNewlyUpdatedLookupColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'branchID' AND Object_ID = Object_ID(N'branches'))
            BEGIN
                ALTER TABLE dbo.branches DROP COLUMN branchID;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'DeptID' AND Object_ID = Object_ID(N'departments'))
            BEGIN
                ALTER TABLE dbo.departments DROP COLUMN DeptID;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'extid' AND Object_ID = Object_ID(N'name_prefixes'))
            BEGIN
                ALTER TABLE dbo.name_prefixes DROP COLUMN extid;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'civilstatusid' AND Object_ID = Object_ID(N'civil_status'))
            BEGIN
                ALTER TABLE dbo.civil_status DROP COLUMN civilstatusid;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'citizenshipid' AND Object_ID = Object_ID(N'citizenships'))
            BEGIN
                ALTER TABLE dbo.citizenships DROP COLUMN citizenshipid;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'bloodgroupid' AND Object_ID = Object_ID(N'blood_types'))
            BEGIN
                ALTER TABLE dbo.blood_types DROP COLUMN bloodgroupid;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'empstatus_id' AND Object_ID = Object_ID(N'employment_types'))
            BEGIN
                ALTER TABLE dbo.employment_types DROP COLUMN empstatus_id;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'PosID' AND Object_ID = Object_ID(N'positions'))
            BEGIN
                ALTER TABLE dbo.positions DROP COLUMN PosID;
            END

            IF EXISTS(SELECT TOP(1) * FROM sys.columns WHERE Name = N'PID' AND Object_ID = Object_ID(N'plantillas'))
            BEGIN
                ALTER TABLE dbo.plantillas DROP COLUMN PID;
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
