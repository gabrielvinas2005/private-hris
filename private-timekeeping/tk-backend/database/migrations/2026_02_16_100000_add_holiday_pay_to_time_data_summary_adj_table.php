<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary_adj', function (Blueprint $table) {
            $table->decimal('Holiday_Pay', 18, 3)->default(0)->after('Overtime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data_summary_adj', function (Blueprint $table) {
            $table->dropColumn('Holiday_Pay');
        });
    }
};
