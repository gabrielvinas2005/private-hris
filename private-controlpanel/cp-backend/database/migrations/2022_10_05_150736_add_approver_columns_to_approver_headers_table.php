<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApproverColumnsToApproverHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('approver_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->default(0)->nullable();
            $table->unsignedBigInteger('division_id')->default(0)->nullable();
            $table->unsignedBigInteger('section_id')->default(0)->nullable();
            $table->integer('branch_approver_id_1')->default(0)->nullable();
            $table->integer('division_approver_id_1')->default(0)->nullable();
            $table->integer('section_approver_id_1')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('approver_headers', function (Blueprint $table) {
            $table->dropColumn('branch_id');
            $table->dropColumn('division_id');
            $table->dropColumn('section_id');
            $table->dropColumn('branch_approver_id_1');
            $table->dropColumn('division_approver_id_1');
            $table->dropColumn('section_approver_id_1');
        });
    }
}
