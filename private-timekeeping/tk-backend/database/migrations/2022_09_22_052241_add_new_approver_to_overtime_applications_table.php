<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewApproverToOvertimeApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->boolean('approved_2')->default(0)->nullable();
            $table->boolean('disapproved_2')->default(0)->nullable();
            $table->string('disapproved_2_remark')->nullable();
            $table->boolean('approved_3')->default(0)->nullable();
            $table->boolean('disapproved_3')->default(0)->nullable();
            $table->string('disapproved_3_remark')->nullable();
            $table->integer('processed_by')->default(0)->nullable();
            $table->dateTime('processed_date')->nullable();
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
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->dropColumn('approved_2');
            $table->dropColumn('disapproved_2');
            $table->dropColumn('disapproved_2_remark');
            $table->dropColumn('approved_3');
            $table->dropColumn('disapproved_3');
            $table->dropColumn('disapproved_3_remark');
            $table->dropColumn('processed_by');
            $table->dropColumn('processed_date');
            $table->dropColumn('processed_by_2');
            $table->dropColumn('processed_date_2');
            $table->dropColumn('processed_by_3');
            $table->dropColumn('processed_date_3');
        });
    }
}
