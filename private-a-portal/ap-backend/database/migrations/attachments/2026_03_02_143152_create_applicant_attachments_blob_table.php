<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantAttachmentsBlobTable extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'attachments';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('applicant_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('applicant_id');
            $table->string('attachment_name', 255);
            $table->text('path')->nullable();
            $table->longText('file_content'); // base64 string
            $table->integer('file_size');
            $table->string('file_type', 100)->nullable();
            $table->timestamps();

            $table->index('applicant_id', 'IX_applicant_attachments_applicant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('applicant_attachments');
    }
}
