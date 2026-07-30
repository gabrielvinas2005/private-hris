<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AddDefaultUserToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $admin_user = [
            'name' => 'Administrator',
            'email' => 'asu.admin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password@123'),
            'active' => true,
            'is_admin' => true,
            'has_change_password' => true,
            'access_all_branches' => true
        ];

        DB::table('users')->insert($admin_user);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
