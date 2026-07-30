<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
        Schema::connection('attachments')->create('non_dtr_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('non_dtr_task_id');
            $table->unsignedBigInteger('non_dtr_entry_id')->nullable();
            $table->string('file_name');
            $table->binary('file_content');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->index('non_dtr_task_id');
            $table->index('non_dtr_entry_id');
            $table->index('uploaded_by');
        });

        DB::connection('attachments')->statement(
            'ALTER TABLE non_dtr_attachments ALTER COLUMN file_content VARBINARY(MAX) NOT NULL'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('attachments')->dropIfExists('non_dtr_attachments');
    }
};
