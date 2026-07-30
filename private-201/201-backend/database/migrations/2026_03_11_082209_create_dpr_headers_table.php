<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDprHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dpcr_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('month_from');
            $table->unsignedBigInteger('month_to');
            $table->boolean('is_posted')->default(0);
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent();
            $table->unsignedBigInteger('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dpcr_headers');
    }
}
