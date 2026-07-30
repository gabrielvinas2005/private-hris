<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlantillaToServiceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->string('plantilla')->nullable();
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('section')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropColumn(['plantilla', 'company', 'department', 'section']);
        });
    }
}
