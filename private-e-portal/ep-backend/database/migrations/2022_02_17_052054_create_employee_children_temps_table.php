<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeChildrenTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_children_temps', function (Blueprint $table) {
            $table->bigIncrements('children_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->string('child_name')->nullable();
            $table->date('child_birthdate')->nullable();
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
        Schema::dropIfExists('employee_children_temps');
    }
}
