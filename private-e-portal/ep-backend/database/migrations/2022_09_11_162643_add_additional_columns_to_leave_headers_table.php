<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->integer('incase_vacation_leave_id')->default(0)->nullable();
            $table->string('incase_vacation_leave_specify')->nullable();
            $table->integer('incase_sick_leave_id')->default(0)->nullable();
            $table->string('incase_sick_leave_specify')->nullable();
            $table->string('incase_special_leave_specify')->nullable();
            $table->integer('incase_study_leave_id')->default(0)->nullable();
            $table->integer('other_purpose_id')->default(0)->nullable();
            $table->integer('commutation_id')->default(0)->nullable();
            $table->boolean('approved_2')->default(0)->nullable();
            $table->boolean('disapproved_2')->default(0)->nullable();
            $table->boolean('approved_3')->default(0)->nullable();
            $table->boolean('disapproved_3')->default(0)->nullable();
            $table->integer('processed_by_2')->default(0)->nullable();
            $table->dateTime('processed_date_2')->nullable();
            $table->integer('processed_by_3')->default(0)->nullable();
            $table->dateTime('processed_date_3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->dropColumn('incase_vacation_leave_id');
            $table->dropColumn('incase_vacation_leave_specify');
            $table->dropColumn('incase_sick_leave_id');
            $table->dropColumn('incase_sick_leave_specify');
            $table->dropColumn('incase_special_leave_specify');
            $table->dropColumn('incase_study_leave_id');
            $table->dropColumn('other_purpose_id');
            $table->dropColumn('commutation_id');
            $table->dropColumn('approved_2');
            $table->dropColumn('disapproved_2');
            $table->dropColumn('approved_3');
            $table->dropColumn('disapproved_3');
            $table->dropColumn('processed_by_2');
            $table->dropColumn('processed_date_2');
            $table->dropColumn('processed_by_3');
            $table->dropColumn('processed_date_3');
        });
    }
}
