<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPlantillasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plantillas', function (Blueprint $table) {
            $table->integer('department_id')->nullable();
            $table->string('eligibility')->nullable();
            $table->string('experience', 3000)->nullable();
            $table->string('training', 3000)->nullable();
            $table->string('education', 3000)->nullable();
            $table->string('unit', 3000)->nullable();
            $table->date('publication_from')->nullable();
            $table->date('publication_to')->nullable();
            $table->string('status', 100)->nullable();
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
            $table->dropColumn('department_id');
            $table->dropColumn('eligibility');
            $table->dropColumn('experience');
            $table->dropColumn('training');
            $table->dropColumn('education');
            $table->dropColumn('unit');
            $table->dropColumn('publication_from');
            $table->dropColumn('publication_to');
            $table->dropColumn('status');
        });
    }
}
