<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonetizationFieldsToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->boolean('monetization')->default(false)->nullable();
            $table->decimal('monetization_amount', 15, 2)->default(0)->nullable();
            $table->boolean('terminal_leave')->default(false)->nullable();
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
            $table->dropColumn('monetization');
            $table->dropColumn('monetization_amount');
            $table->dropColumn('terminal_leave');
        });
    }
}
