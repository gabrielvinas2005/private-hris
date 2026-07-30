<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficialBusinessApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('official_business_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->dateTime('date')->nullable();
            $table->dateTime('date_time_from')->nullable();
            $table->dateTime('date_time_to')->nullable();
            $table->string('client')->nullable();
            $table->string('purpose')->nullable();
            $table->boolean('approved')->default(0)->nullable();
            $table->boolean('disapproved')->default(0)->nullable();
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
        Schema::dropIfExists('official_business_applications');
    }
}
