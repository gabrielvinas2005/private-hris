<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBirthdayNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('birthday_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            // Year and month this birthday notification refers to
            $table->integer('year');
            $table->integer('month');
            // When the notification was actually sent
            $table->date('notification_date');
            // Flags to indicate if notifications were sent successfully
            $table->boolean('sent_to_hr')->default(false);
            $table->boolean('sent_to_employee')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('birthday_notifications');
    }
}
