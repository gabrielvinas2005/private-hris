<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToWorkCancellationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_cancellations', function (Blueprint $table) {
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_cancellations', function (Blueprint $table) {
            $table->dropColumn('time_from');
            $table->dropColumn('time_to');
        });
    }
}
