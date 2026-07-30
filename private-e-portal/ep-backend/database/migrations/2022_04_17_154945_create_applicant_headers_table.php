<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicant_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('applicant_no')->unique();
            $table->text('photo')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->default(0)->nullable();
            $table->integer('gender')->default(0);
            $table->string('mobile_no')->nullable();
            $table->string('email')->unique();
            $table->string('employee_no')->nullable();
            $table->string('resume')->nullable();
            $table->integer('application_status_id')->default(0);
            $table->date('application_date')->nullable();
            $table->integer('user_id')->default(0);
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
        Schema::dropIfExists('applicant_headers');
    }
}
