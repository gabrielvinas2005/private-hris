<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewDateRemarkColumnsToStepIncrementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('step_increments', function (Blueprint $table) {
            $table->dateTime('forwarded_date')->nullable();
            $table->text('cancelled_remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('step_increments', function (Blueprint $table) {
            //
        });
    }
}
