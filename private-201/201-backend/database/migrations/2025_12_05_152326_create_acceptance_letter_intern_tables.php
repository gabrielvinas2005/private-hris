<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcceptanceLetterInternTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Main table for acceptance letter intern
        Schema::create('acceptance_letter_intern', function (Blueprint $table) {
            $table->id();
            $table->string('school_officer', 255);
            $table->string('school_name', 255);
            $table->string('school_address', 500)->nullable();
            $table->date('date_of_start');
            $table->string('signatory', 255)->default('MA FE J. AVILA');
            $table->string('position', 255)->default('OIC Executive Director');
            $table->timestamps();
        });

        // Students table for acceptance letter intern
        Schema::create('acceptance_letter_intern_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('acceptance_letter_intern_id');
            $table->string('first_name', 255);
            $table->string('middle_name', 255)->nullable();
            $table->string('last_name', 255);
            $table->timestamps();

            $table->foreign('acceptance_letter_intern_id')
                ->references('id')
                ->on('acceptance_letter_intern')
                ->onDelete('cascade');
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
            $table->dropForeign(['acceptance_letter_intern_id']);
        });
        Schema::dropIfExists('acceptance_letter_intern_students');
        Schema::dropIfExists('acceptance_letter_intern');
    }
}
