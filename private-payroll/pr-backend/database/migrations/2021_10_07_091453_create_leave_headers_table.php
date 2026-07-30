<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0)->nullable();
            $table->integer('leave_type_id')->default(0)->nullable();
            $table->integer('day_type_id')->default(0)->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('leave_headers');
    }
}
