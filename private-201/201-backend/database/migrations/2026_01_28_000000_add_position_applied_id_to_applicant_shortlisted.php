<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionAppliedIdToApplicantShortlisted extends Migration
{
    /**
     * Run the migrations.
     * Shortlisting is per (applicant, position) so one applicant can be shortlisted for one job but not another.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('applicant_shortlisted', 'position_applied_id')) {
            Schema::table('applicant_shortlisted', function (Blueprint $table) {
                $table->unsignedBigInteger('position_applied_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('applicant_shortlisted', 'position_applied_id')) {
            Schema::table('applicant_shortlisted', function (Blueprint $table) {
                $table->dropColumn('position_applied_id');
            });
        }
    }
}
