<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEncryptionScripts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement(
            "IF EXISTS (select * from sysobjects where name = 'ufn_EncryptString')
            DROP FUNCTION ufn_EncryptString;"
        );

        DB::statement("
            CREATE FUNCTION [dbo].[ufn_EncryptString] ( @pClearString VARCHAR(MAX) )
            RETURNS NVARCHAR(MAX) WITH ENCRYPTION AS
            BEGIN
                
                DECLARE @vEncryptedString NVARCHAR(MAX)
                DECLARE @vIdx INT
                DECLARE @vBaseIncrement INT
                
                SET @vIdx = 1
                SET @vBaseIncrement = 128
                SET @vEncryptedString = ''
                
                WHILE @vIdx <= LEN(@pClearString)
                BEGIN
                    SET @vEncryptedString = @vEncryptedString + 
                                            NCHAR(ASCII(SUBSTRING(@pClearString, @vIdx, 1)) +
                                            @vBaseIncrement + @vIdx - 1)
                    SET @vIdx = @vIdx + 1
                END
                
                RETURN @vEncryptedString

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
        DB::statement(
            "IF EXISTS (select * from sysobjects where name = 'ufn_EncryptString')
            DROP FUNCTION ufn_EncryptString;"
        );
    }
}
