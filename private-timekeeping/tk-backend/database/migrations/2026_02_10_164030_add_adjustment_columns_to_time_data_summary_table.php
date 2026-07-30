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
        Schema::table('time_data_summary', function (Blueprint $table) {
            $table->decimal('Adjustment_Amount', 18, 2)->default(0.00)->after('Total_Deduction');
            $table->integer('Adjustment_Period_ID')->nullable()->after('Adjustment_Amount');
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
            $table->dropColumn(['Adjustment_Amount', 'Adjustment_Period_ID']);
        });
    }
};
