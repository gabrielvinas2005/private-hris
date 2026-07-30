<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterPhotoSizeToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
        IF EXISTS (select * from sysobjects where name = 'DF__employees__photo__114A936A')
        ALTER TABLE employees DROP CONSTRAINT DF__employees__photo__114A936A
        ");

        DB::statement("ALTER TABLE employees ALTER COLUMN photo VARCHAR(MAX);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
}
