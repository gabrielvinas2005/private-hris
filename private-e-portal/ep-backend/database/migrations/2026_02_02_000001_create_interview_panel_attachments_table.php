<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewPanelAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_panel_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('interview_id');
            $table->integer('employee_id');
            $table->integer('applicant_id');

            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_panel_attachments');
    }
}

