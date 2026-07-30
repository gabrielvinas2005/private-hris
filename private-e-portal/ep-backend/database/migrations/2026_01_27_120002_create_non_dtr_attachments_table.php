<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonDtrAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_dtr_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('non_dtr_task_id');
            $table->unsignedBigInteger('non_dtr_entry_id')->nullable(); // Can be attached to specific entry or overall task
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // image, pdf, document, etc.
            $table->integer('file_size')->nullable(); // in bytes
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('non_dtr_task_id')
                ->references('id')
                ->on('non_dtr_task')
                ->onDelete('cascade');

            // Indexes
            $table->index('non_dtr_task_id');
            $table->index('non_dtr_entry_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('non_dtr_attachments');
    }
}
