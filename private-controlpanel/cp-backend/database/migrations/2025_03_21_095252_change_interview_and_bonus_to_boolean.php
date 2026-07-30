<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeInterviewAndBonusToBoolean extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ⚠️ Drop the columns first before recreating them
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->dropColumn(['interview', 'bonus']);
        });

        // ✅ Add the columns again as boolean with default values
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->boolean('interview')->default(false);
            $table->boolean('bonus')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ⚠️ Drop the columns to revert
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->dropColumn(['interview', 'bonus']);
        });

        // ✅ Recreate the columns as text
        Schema::table('applicant_eete_ratings', function (Blueprint $table) {
            $table->text('interview')->nullable();
            $table->text('bonus')->nullable();
        });
    }
}
