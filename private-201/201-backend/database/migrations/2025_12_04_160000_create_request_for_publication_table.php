<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestForPublicationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_for_publication', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('plantilla_id');
            $table->date('date_requested');
            $table->string('remarks', 500)->nullable();
            $table->string('status')->default('Pending');
            $table->timestamps();

            $table->foreign('plantilla_id')
                ->references('id')
                ->on('plantillas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_for_publication', function (Blueprint $table) {
            $table->dropForeign(['plantilla_id']);
        });

        Schema::dropIfExists('request_for_publication');
    }
}
