<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDoneToApplicantInterviewHeaders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('applicant_interview_headers')) {
            return;
        }
        Schema::table('applicant_interview_headers', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_interview_headers', 'is_done')) {
                $table->boolean('is_done')->default(false);
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('applicant_interview_headers')) {
            return;
        }
        Schema::table('applicant_interview_headers', function (Blueprint $table) {
            if (Schema::hasColumn('applicant_interview_headers', 'is_done')) {
                $table->dropColumn('is_done');
            }
        });
    }
}

