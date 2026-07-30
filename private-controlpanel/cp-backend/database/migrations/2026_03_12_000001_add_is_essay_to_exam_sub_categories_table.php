<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsEssayToExamSubCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('exam_sub_categories')) {
            return;
        }

        Schema::table('exam_sub_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_sub_categories', 'is_essay')) {
                $table->boolean('is_essay')->default(false)->after('existing_questions');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('exam_sub_categories')) {
            return;
        }

        Schema::table('exam_sub_categories', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sub_categories', 'is_essay')) {
                $table->dropColumn('is_essay');
            }
        });
    }
}

