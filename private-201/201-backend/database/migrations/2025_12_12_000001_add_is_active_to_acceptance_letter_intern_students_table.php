<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToAcceptanceLetterInternStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('acceptance_letter_intern_students', function (Blueprint $table) {
            if (!Schema::hasColumn('acceptance_letter_intern_students', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('last_name');
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
        Schema::table('acceptance_letter_intern_students', function (Blueprint $table) {
            if (Schema::hasColumn('acceptance_letter_intern_students', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
}
