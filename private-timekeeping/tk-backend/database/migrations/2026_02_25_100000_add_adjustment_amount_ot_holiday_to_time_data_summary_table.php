<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Adjustment_Amount_OT_Holiday: sum of Adj. Overtime + Adj. Holiday Pay from
     * preceding period. Net preceding period effect = Adjustment_Amount − Adjustment_Amount_OT_Holiday
     * (e.g. 467.88 − 856.71 = −388.83).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->decimal('Adjustment_Amount_OT_Holiday', 18, 3)->default(0)->after('Adjustment_Amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->dropColumn('Adjustment_Amount_OT_Holiday');
        });
    }
};
