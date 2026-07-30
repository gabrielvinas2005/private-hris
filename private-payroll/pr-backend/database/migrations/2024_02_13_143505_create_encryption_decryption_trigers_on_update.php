<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEncryptionDecryptionTrigersOnUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            CREATE TRIGGER trg_encrypt_employees_U  ON employees 
                FOR UPDATE
            AS
            BEGIN
                    Update employees SET
                            first_name = dbo.ufn_EncryptString(first_name),
                            middle_name = dbo.ufn_EncryptString(middle_name),
                            last_name = dbo.ufn_EncryptString(last_name),
                            email = dbo.ufn_EncryptString(email),
                            mobile_no = dbo.ufn_EncryptString(mobile_no),
                            telephone_no = dbo.ufn_EncryptString(telephone_no),
                            father_first_name = dbo.ufn_EncryptString(father_first_name),
                            father_middle_name = dbo.ufn_EncryptString(father_middle_name),
                            father_last_name = dbo.ufn_EncryptString(father_last_name),
                            mother_first_name = dbo.ufn_EncryptString(mother_first_name),
                            mother_middle_name = dbo.ufn_EncryptString(mother_middle_name),
                            mother_last_name = dbo.ufn_EncryptString(mother_last_name),
                            spouse_first_name = dbo.ufn_EncryptString(spouse_first_name),
                            spouse_middle_name = dbo.ufn_EncryptString(spouse_middle_name),
                            spouse_last_name = dbo.ufn_EncryptString(spouse_last_name),
                            is_encrypted = 1
                    WHERE employees.id IN (SELECT id FROM INSERTED)
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
        DB::unprepared("
            IF EXISTS (select * from sysobjects where name = 'trg_encrypt_employees_U')
                DROP TRIGGER trg_encrypt_employees_U;
        ");
    }
}
