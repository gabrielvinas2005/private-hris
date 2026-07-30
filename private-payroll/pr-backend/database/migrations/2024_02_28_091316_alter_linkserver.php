<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterLinkserver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // DB::unprepared("
        //     USE master;

        //     IF EXISTS(select * from sys.syslogins where name = N'LRA_HRMS_USER')
        //         BEGIN
        //             GRANT EXECUTE ON SYS.XP_PROP_OLEDB_PROVIDER TO LRA_HRMS_USER;
        //         END
        // ");

        DB::unprepared("
            IF EXISTS(select * from sys.syslogins where name = N'LRA_HRMS_USER')
                BEGIN
                    ALTER SERVER ROLE sysadmin ADD MEMBER LRA_HRMS_USER;
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
