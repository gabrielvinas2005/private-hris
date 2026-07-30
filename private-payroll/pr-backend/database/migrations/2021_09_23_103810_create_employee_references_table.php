<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_references', function (Blueprint $table) {
            $table->bigIncrements('reference_id');
            $table->integer('employee_id')->default();
            $table->string('ref_name')->nullable();
            $table->string('ref_address')->nullable();
            $table->string('ref_occupation')->nullable();
            $table->string('ref_contact_no')->nullable();
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
        Schema::dropIfExists('employee_references');
    }
}
