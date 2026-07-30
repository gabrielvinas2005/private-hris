<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToGsisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gsis', function (Blueprint $table) {
            $table->date('effectivity_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('employer_share', 18, 2)->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gsis', function (Blueprint $table) {
            $table->dropColumn('effectivity_date');
            $table->dropColumn('end_date');
            $table->dropColumn('employer_share');
        });
    }
}
