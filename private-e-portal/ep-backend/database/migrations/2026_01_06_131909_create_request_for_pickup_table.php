<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestForPickupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_for_pickup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ob_id'); // Foreign key to official_business_applications
            $table->string('from')->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_no')->nullable();
            $table->text('documents_materials')->nullable();
            $table->string('picked_up_by')->nullable();
            $table->date('pickup_date')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('received_by')->nullable();
            $table->date('date')->nullable(); // Top date field
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('ob_id')->references('id')->on('official_business_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_for_pickup');
    }
}
