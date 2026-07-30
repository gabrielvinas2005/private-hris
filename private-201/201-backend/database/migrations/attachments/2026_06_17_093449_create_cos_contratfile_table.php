<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCosContratfileTable extends Migration
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
        Schema::connection('attachments')->create('cos_contractfile', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cos_contract_id');
            $table->string('file_name', 255);
            $table->text('file_content');
            $table->integer('file_size');
            $table->string('file_type', 100)->nullable();
            $table->text('path')->nullable();
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
        Schema::connection('attachments')->dropIfExists('cos_contractfile');
        
    }
}
