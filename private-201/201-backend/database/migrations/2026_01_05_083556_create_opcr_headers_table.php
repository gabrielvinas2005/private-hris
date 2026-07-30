<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpcrHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opcr_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('division_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBiginteger('month_from');
            $table->unsignedBiginteger('month_to');
            $table->boolean('is_posted')->default(0);
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent();
            $table->unsignedBiginteger('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opcr_headers');
    }
}
