<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropUniqueNameToSubcompetenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subcompetencies', function (Blueprint $table) {
            DB::statement("
                IF EXISTS (select * from sysobjects where name = 'subcompetencies_code_unique')
                DROP INDEX [subcompetencies_code_unique] ON [dbo].[subcompetencies];
            ");

            DB::statement("
                IF EXISTS (select * from sysobjects where name = 'subcompetencies_name_unique')
                DROP INDEX [subcompetencies_name_unique] ON [dbo].[subcompetencies];
            ");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subcompetencies', function (Blueprint $table) {
            //
        });
    }
}
