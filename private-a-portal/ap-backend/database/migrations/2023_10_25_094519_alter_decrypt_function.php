<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterDecryptFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            ALTER FUNCTION [dbo].[ufn_DecryptString] ( @pEncryptedString NVARCHAR(100),@key NVARCHAR(MAX) )
            RETURNS VARCHAR(100) WITH ENCRYPTION AS
            BEGIN

            DECLARE @pass_key NVARCHAR(MAX) 

            SET @pass_key = (SELECT TOP(1) ISNULL(pass_key,'') FROM app_key)

            IF @key <> @pass_key
                BEGIN
                    RETURN ''
                END

            DECLARE @vClearString VARCHAR(100)
            DECLARE @vIdx INT
            DECLARE @vBaseIncrement INT

            SET @vIdx = 1
            SET @vBaseIncrement = 128
            SET @vClearString = ''

            WHILE @vIdx <= LEN(@pEncryptedString)
            BEGIN
                SET @vClearString = @vClearString + 
                                    CHAR(UNICODE(SUBSTRING(@pEncryptedString, @vIdx, 1)) - 
                                    @vBaseIncrement - @vIdx + 1)
                SET @vIdx = @vIdx + 1
            END

            RETURN @vClearString

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
