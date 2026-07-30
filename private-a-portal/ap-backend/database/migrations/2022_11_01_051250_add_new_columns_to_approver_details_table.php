<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToApproverDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('approver_details', function (Blueprint $table) {
            $table->boolean('is_branch')->default(0)->nullable();
            $table->boolean('is_department')->default(0)->nullable();
            $table->boolean('is_division')->default(0)->nullable();
            $table->boolean('is_section')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('approver_details', function (Blueprint $table) {
            $table->dropColumn('is_branch');
            $table->dropColumn('is_department');
            $table->dropColumn('is_division');
            $table->dropColumn('is_section');
        });
    }
}
