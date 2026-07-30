<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterPhotoSizeToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
        IF EXISTS (select * from sysobjects where name = 'DF__companies__logo__395884C4')
        ALTER TABLE companies DROP CONSTRAINT DF__companies__logo__395884C4
        ");

        DB::statement("ALTER TABLE companies ALTER COLUMN logo VARCHAR(MAX);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            //
        });
    }
}
