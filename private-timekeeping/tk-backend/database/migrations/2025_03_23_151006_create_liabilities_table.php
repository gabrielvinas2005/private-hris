<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saln_liabilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id'); // Linked to user
            $table->string('nature')->nullable(); // Nature of liability
            $table->string('creditor_name')->nullable(); // Creditor name
            $table->decimal('outstanding_balance', 15, 2)->nullable(); // Outstanding balance
            $table->timestamps();

            // Foreign key constraint to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saln_liabilities');
    }
}
