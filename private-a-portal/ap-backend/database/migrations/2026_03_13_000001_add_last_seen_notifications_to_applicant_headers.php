<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_headers', function (Blueprint $table) {
            $table->dateTime('last_seen_application_notifications_at')->nullable();
            $table->dateTime('last_seen_exam_notifications_at')->nullable();
            $table->dateTime('last_seen_interview_notifications_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_headers', function (Blueprint $table) {
            $table->dropColumn([
                'last_seen_application_notifications_at',
                'last_seen_exam_notifications_at',
                'last_seen_interview_notifications_at',
            ]);
        });
    }
};

