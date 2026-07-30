<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfSupervisedPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PDF_SupervisedPositions', function (Blueprint $table) {
            $table->id();
            // Match the data type of PDF.id (int) to avoid FK mismatch
            $table->unsignedInteger('PDF_id');
            $table->unsignedBigInteger('supervised_positionTitle_ID')->nullable();
            $table->string('supervised_item_number', 250)->nullable();
            $table->timestamps();

            $table->foreign('PDF_id')
                ->references('id')
                ->on('PDF')
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
        Schema::dropIfExists('PDF_SupervisedPositions');
    }
}
