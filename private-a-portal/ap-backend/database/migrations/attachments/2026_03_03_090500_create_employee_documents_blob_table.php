<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDocumentsBlobTable extends Migration
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
        Schema::connection($this->connection)->create('employee_documents', function (Blueprint $table) {
            $table->bigIncrements('employee_document_id');
            $table->unsignedBigInteger('employee_id')->default(0);

            // Metadata (kept similar to the main DB table)
            $table->string('name')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->text('description')->nullable();
            $table->text('attachment_name')->nullable();
            $table->text('path')->nullable();
            $table->string('extension', 20)->nullable();

            // Actual file payload stored in the attachments DB (base64 string)
            $table->longText('file_content');
            $table->integer('file_size'); // bytes
            $table->string('file_type', 100)->nullable(); // MIME type

            $table->timestamps();

            $table->index('employee_id', 'IX_employee_documents_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('employee_documents');
    }
}

