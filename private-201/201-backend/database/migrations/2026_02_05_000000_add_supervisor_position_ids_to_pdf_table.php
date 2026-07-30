<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupervisorPositionIdsToPdfTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('PDF', function (Blueprint $table) {
            $table->unsignedBigInteger('immediate_supervisor_position_id')->nullable()->after('supervisor');
            $table->unsignedBigInteger('next_higher_supervisor_position_id')->nullable()->after('immediate_supervisor_position_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('PDF', function (Blueprint $table) {
            $table->dropColumn([
                'immediate_supervisor_position_id',
                'next_higher_supervisor_position_id'
            ]);
        });
    }
}
