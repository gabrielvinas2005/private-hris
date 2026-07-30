<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCosContractNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cos_contract_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cos_contract_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('expiration_date');
            $table->date('notification_date');
            $table->boolean('sent_to_hr')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['cos_contract_id', 'notification_date']);
            $table->index(['employee_id', 'notification_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cos_contract_notifications');
    }
}

