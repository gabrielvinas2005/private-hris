<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupportingDocumentToInterviewPanelRatings extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('interview_panel_ratings')) {
            return;
        }
        Schema::table('interview_panel_ratings', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_panel_ratings', 'supporting_document')) {
                $table->string('supporting_document', 1024)->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('interview_panel_ratings')) {
            return;
        }
        Schema::table('interview_panel_ratings', function (Blueprint $table) {
            if (Schema::hasColumn('interview_panel_ratings', 'supporting_document')) {
                $table->dropColumn('supporting_document');
            }
        });
    }
}
