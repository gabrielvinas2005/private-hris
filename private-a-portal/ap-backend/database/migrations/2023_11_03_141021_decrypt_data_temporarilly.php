<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DecryptDataTemporarilly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $app_key = env('APP_KEY', '');

        DB::statement("
            Update employees SET
                    first_name = dbo.ufn_DecryptString(first_name,'$app_key'),
                    middle_name = dbo.ufn_DecryptString(middle_name,'$app_key'),
                    last_name = dbo.ufn_DecryptString(last_name,'$app_key'),
                    email = dbo.ufn_DecryptString(email,'$app_key'),
                    mobile_no = dbo.ufn_DecryptString(mobile_no,'$app_key'),
                    telephone_no = dbo.ufn_DecryptString(telephone_no,'$app_key'),
                    father_first_name = dbo.ufn_DecryptString(father_first_name,'$app_key'),
                    father_middle_name = dbo.ufn_DecryptString(father_middle_name,'$app_key'),
                    father_last_name = dbo.ufn_DecryptString(father_last_name,'$app_key'),
                    mother_first_name = dbo.ufn_DecryptString(mother_first_name,'$app_key'),
                    mother_middle_name = dbo.ufn_DecryptString(mother_middle_name,'$app_key'),
                    mother_last_name = dbo.ufn_DecryptString(mother_last_name,'$app_key'),
                    spouse_first_name = dbo.ufn_DecryptString(spouse_first_name,'$app_key'),
                    spouse_middle_name = dbo.ufn_DecryptString(spouse_middle_name,'$app_key'),
                    spouse_last_name = dbo.ufn_DecryptString(spouse_last_name,'$app_key'),
                    is_encrypted = 0
            WHERE is_encrypted = 1
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
