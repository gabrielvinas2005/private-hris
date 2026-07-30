<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddObTypesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $menu_exist = DB::table('official_business_types')->get();

        if ($menu_exist->isEmpty()) {
            DB::statement("
            
                SET IDENTITY_INSERT [dbo].[official_business_types] ON
                INSERT INTO [dbo].[official_business_types] ([id], [name], [active], [created_at], [updated_at]) 
                VALUES (1, N'Official Business', 1, NULL, NULL);
                INSERT INTO [dbo].[official_business_types] ([id], [name], [active], [created_at], [updated_at]) 
                VALUES (2, N'Personal', 1, NULL, NULL);
                INSERT INTO [dbo].[official_business_types] ([id], [name], [active], [created_at], [updated_at]) 
                VALUES (3, N'Travel Authority', 1, NULL, NULL);
                SET IDENTITY_INSERT [dbo].[official_business_types] OFF
                ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menus', function (Blueprint $table) {
            //
        });
    }
}
