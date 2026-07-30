<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhotoToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('photo')->nullable();
            $table->boolean('active')->nullable();
            $table->boolean('locked')->nullable();
            $table->datetime('locked_date')->nullable();
            $table->string('employee_no')->nullable();
            $table->string('professor_no')->nullable();
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
            $table->dropColumn('photo');
            $table->dropColumn('active');
            $table->dropColumn('locked');
            $table->dropColumn('locked_date');
            $table->dropColumn('employee_no');
            $table->dropColumn('professor_no');
        });
    }
}
