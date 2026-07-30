<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidayTaggingHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holiday_tagging_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id')->nullable();
            $table->integer('holiday_type_id')->nullable();
            $table->text('year')->nullable();
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
        Schema::dropIfExists('holiday_tagging_headers');
    }
}
