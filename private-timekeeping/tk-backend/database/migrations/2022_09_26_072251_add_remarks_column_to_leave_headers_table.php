<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksColumnToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->string('approved_remarks')->nullable();
            $table->string('disapproved_remarks')->nullable();
            $table->string('approved_2_remarks')->nullable();
            $table->string('disapproved_2_remarks')->nullable();
            $table->string('approved_3_remarks')->nullable();
            $table->string('disapproved_3_remarks')->nullable();
            $table->boolean('is_cancel')->default(0)->nullable();
            $table->integer('canceled_by')->default(0)->nullable();
            $table->dateTime('canceled_date')->nullable();
            $table->string('canceled_remarks')->nullable();
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
            $table->dropColumn('approved_remarks');
            $table->dropColumn('disapproved_remarks');
            $table->dropColumn('approved_2_remarks');
            $table->dropColumn('disapproved_2_remarks');
            $table->dropColumn('approved_3_remarks');
            $table->dropColumn('disapproved_3_remarks');
            $table->dropColumn('is_cancel');
            $table->dropColumn('canceled_by');
            $table->dropColumn('canceled_date');
            $table->dropColumn('canceled_remarks');
        });
    }
}
