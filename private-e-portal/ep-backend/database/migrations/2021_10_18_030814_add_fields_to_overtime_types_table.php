<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOvertimeTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_types', function (Blueprint $table) {
            $table->time('nd_from')->nullable();
            $table->time('nd_to')->nullable();
            $table->decimal('nd_rating')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_types', function (Blueprint $table) {
            $table->dropColumn('nd_from');
            $table->dropColumn('nd_to');
            $table->dropColumn('nd_rating');
        });
    }
}
