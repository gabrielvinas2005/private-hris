<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExamTypeIdToExaminationSetupHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examination_setup_header', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_type_id')->nullable()->after('category_id');
            $table->foreign('exam_type_id')
                ->references('id')
                ->on('exam_types')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('examination_setup_header', function (Blueprint $table) {
            $table->dropForeign(['exam_type_id']);
            $table->dropColumn('exam_type_id');
        });
    }
}
