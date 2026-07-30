<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseProgramToAcceptanceLetterInternTable extends Migration
{
    public function up()
    {
        Schema::table('acceptance_letter_intern', function (Blueprint $table) {
            if (!Schema::hasColumn('acceptance_letter_intern', 'course_program')) {
                $table->string('course_program', 500)->nullable()->after('school_address');
            }
        });
    }

    public function down()
    {
        Schema::table('acceptance_letter_intern', function (Blueprint $table) {
            if (Schema::hasColumn('acceptance_letter_intern', 'course_program')) {
                $table->dropColumn('course_program');
            }
        });
    }
}
