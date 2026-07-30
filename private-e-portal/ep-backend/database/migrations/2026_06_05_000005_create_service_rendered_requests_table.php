<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_rendered_requests')) {
            return;
        }

        Schema::create('service_rendered_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->default(0);
            $table->dateTime('request_date')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('noted_by')->nullable();
            $table->string('noted_by_position')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approved_by_position')->nullable();
            $table->boolean('status')->default(0);
            $table->boolean('approved_1')->default(0)->nullable();
            $table->integer('approved_by_1_id')->default(0)->nullable();
            $table->dateTime('approved_date_1')->nullable();
            $table->boolean('disapproved_1')->default(0)->nullable();
            $table->integer('disapproved_by_1_id')->default(0)->nullable();
            $table->dateTime('disapproved_date_1')->nullable();
            $table->boolean('approved_2')->default(0)->nullable();
            $table->integer('approved_by_2_id')->default(0)->nullable();
            $table->dateTime('approved_date_2')->nullable();
            $table->boolean('disapproved_2')->default(0)->nullable();
            $table->integer('disapproved_by_2_id')->default(0)->nullable();
            $table->dateTime('disapproved_date_2')->nullable();
            $table->string('certificate_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rendered_requests');
    }
};
