<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('with_hrm_access')->default(0);
            $table->boolean('with_hrt_access')->default(0);
            $table->boolean('with_hrp_access')->default(0);
            $table->boolean('with_cpm_access')->default(0);
            $table->boolean('is_admin')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('with_hrm_access');
            $table->dropColumn('with_hrt_access');
            $table->dropColumn('with_hrp_access');
            $table->dropColumn('with_cpm_access');
            $table->dropColumn('is_admin');
        });
    }
}
