<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->bigIncrements('employee_document_id');
            $table->unsignedBigInteger('employee_id')->default(0);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('attachment_name')->nullable();
            $table->text('path')->nullable();
            $table->string('extension')->nullable();
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
        Schema::dropIfExists('employee_documents');
    }
}
