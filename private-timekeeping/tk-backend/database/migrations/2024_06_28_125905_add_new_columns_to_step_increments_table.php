<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToStepIncrementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('step_increments', function (Blueprint $table) {
            $table->integer('month_id')->unsigned()->default(0);
            $table->integer('year_id')->unsigned()->default(0);
            $table->integer('approved_by_id')->unsigned()->default(0);
            $table->dateTime('approved_date')->nullable();
            $table->boolean('is_forwarded')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_disapproved')->default(false);
            $table->boolean('is_has_reflected')->default(false);
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
            $table->dropColumn('month_id');
            $table->dropColumn('year_id');
            $table->dropColumn('approved_by_id');
            $table->dropColumn('approved_date');
            $table->dropColumn('is_forwarded');
            $table->dropColumn('is_approved');
            $table->dropColumn('is_disapproved');
        });
    }
}
