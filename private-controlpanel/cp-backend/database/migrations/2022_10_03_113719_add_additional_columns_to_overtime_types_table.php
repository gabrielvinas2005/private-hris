<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToOvertimeTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_types', function (Blueprint $table) {
            $table->decimal('min_ot')->default(0)->nullable();
            $table->decimal('max_ot')->default(0)->nullable();
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
            $table->dropColumn('min_ot');
            $table->dropColumn('max_ot');
        });
    }
}
