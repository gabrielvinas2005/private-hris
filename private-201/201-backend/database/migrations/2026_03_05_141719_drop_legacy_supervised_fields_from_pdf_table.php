<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropLegacySupervisedFieldsFromPdfTable extends Migration
{
    public function up()
    {
        Schema::table('PDF', function (Blueprint $table) {
            $table->dropColumn(['supervised_positionTitle_ID', 'supervised_item_number']);
        });
    }

    public function down()
    {
        Schema::table('PDF', function (Blueprint $table) {
            $table->unsignedBigInteger('supervised_positionTitle_ID')->nullable();
            $table->string('supervised_item_number', 250)->nullable();
        });
    }
}