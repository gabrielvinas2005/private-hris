<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceMilestoneNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_milestone_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            // e.g. 5, 10, 15, ... years of service
            $table->integer('milestone_years');
            $table->date('notification_date');
            $table->boolean('sent_to_hr')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'milestone_years', 'notification_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_milestone_notifications');
    }
}


