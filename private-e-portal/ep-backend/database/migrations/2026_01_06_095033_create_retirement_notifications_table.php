<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetirementNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retirement_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('notification_type'); // 'one_year_before' or 'four_months_before'
            $table->date('retirement_date');
            $table->date('notification_date');
            $table->boolean('sent_to_hr')->default(false);
            $table->boolean('sent_to_employee')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'notification_type', 'notification_date']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retirement_notifications');
    }
}
