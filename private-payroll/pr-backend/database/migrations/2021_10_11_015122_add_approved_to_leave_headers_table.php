<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->boolean('approved')->default(0)->nullable();
            $table->boolean('disapproved')->default(0)->nullable();
            $table->integer('processed_by')->default(0)->nullable();
            $table->date('processed_date')->nullable();
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
            $table->dropColumn('approved');
            $table->dropColumn('disapproved');
            $table->dropColumn('processed_by');
            $table->dropColumn('processed_date');
        });
    }
}
