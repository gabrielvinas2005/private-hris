<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalCancelFieldsToLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->boolean('is_cancel_2')->default(0)->nullable();
            $table->integer('canceled_by_2')->default(0)->nullable();
            $table->dateTime('canceled_date_2')->nullable();
            $table->string('canceled_remarks_2')->nullable();
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
            $table->dropColumn('is_cancel_2');
            $table->dropColumn('canceled_by_2');
            $table->dropColumn('canceled_date_2');
            $table->dropColumn('canceled_remarks_2');
        });
    }
}
