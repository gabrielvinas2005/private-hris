<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostingColumnsToPlantillasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plantillas', function (Blueprint $table) {
            $table->boolean('approved')->default(0)->nullable();
            $table->integer('approved_by')->default(0)->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->boolean('disapproved')->default(0)->nullable();
            $table->integer('disapproved_by')->default(0)->nullable();
            $table->dateTime('disapproved_date')->nullable();
            $table->boolean('cancelled')->default(0)->nullable();
            $table->integer('cancelled_by')->default(0)->nullable();
            $table->dateTime('cancelled_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plantillas', function (Blueprint $table) {
            $table->dropColumn('approved');
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_date');
            $table->dropColumn('disapproved');
            $table->dropColumn('disapproved_by');
            $table->dropColumn('disapproved_date');
            $table->dropColumn('cancelled');
            $table->dropColumn('cancelled_by');
            $table->dropColumn('cancelled_date');
        });
    }
}
