<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOvertimeApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->decimal('ot_amount')->default(0)->nullable();
            $table->decimal('nd_amount')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->dropColumn('ot_amount');
            $table->dropColumn('nd_amount');
        });
    }
}
