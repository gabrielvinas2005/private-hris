<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumsToLeaveMonetizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_monetizations', function (Blueprint $table) {
            $table->integer('approve_2_id')->nullable();
            $table->boolean('approve_2')->nullable();
            $table->dateTime('approve_date_2')->nullable();
            $table->text('approve_2_remarks')->nullable();
            $table->integer('disapprove_2_id')->nullable();
            $table->boolean('disapprove_2')->nullable();
            $table->dateTime('disapprove_date_2')->nullable();
            $table->text('disapprove_2_remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_monetizations', function (Blueprint $table) {
            $table->dropColumn('approve_2_id');
            $table->dropColumn('approve_2');
            $table->dropColumn('approve_date_2');
            $table->dropColumn('approve_2_remarks');
            $table->dropColumn('disapprove_2_id');
            $table->dropColumn('disapprove_2');
            $table->dropColumn('disapprove_date_2');
            $table->dropColumn('disapprove_2_remarks');
        });
    }
}
