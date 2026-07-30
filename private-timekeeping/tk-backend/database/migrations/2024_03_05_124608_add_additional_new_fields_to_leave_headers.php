<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalNewFieldsToLeaveHeaders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_headers', function (Blueprint $table) {
            $table->boolean('is_cancel_3')->default(false)->nullable();
            $table->integer('canceled_by_3')->default(false)->nullable();
            $table->dateTime('canceled_date_3')->nullable();
            $table->string('canceled_remarks_3')->nullable();
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
            //
        });
    }
}
