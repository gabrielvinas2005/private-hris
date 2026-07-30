<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DefineAppKey extends Migration
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
            SET IDENTITY_INSERT [dbo].[app_key] ON
            INSERT INTO [dbo].[app_key] ([id], [pass_key], [created_at], [updated_at]) VALUES (1, N'$app_key', NULL, NULL)
            SET IDENTITY_INSERT [dbo].[app_key] OFF
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
