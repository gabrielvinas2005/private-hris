<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveEncryptionTriggers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
                IF EXISTS (select * from sysobjects where name = 'trg_encrypt_employees_I')
                DROP TRIGGER trg_encrypt_employees_I;
        ");

        DB::statement("
                IF EXISTS (select * from sysobjects where name = 'trg_encrypt_employees_U')
                DROP TRIGGER trg_encrypt_employees_U;
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
