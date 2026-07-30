<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantBoardResolutionDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_board_resolution_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('selection_id');
            $table->integer('applicant_id');
            $table->text('board_resolution_document')->nullable();
            $table->text('board_resolution_document_path')->nullable();
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
        Schema::dropIfExists('applicant_board_resolution_documents');
    }
}
