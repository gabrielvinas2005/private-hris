<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove unused legacy columns from time_data_summary: Active, Approved, Applied, Reference_ID, Is_Edited.
     *
     * @return void
     */
    public function up()
    {
        $columns = ['Active', 'Approved', 'Applied', 'Reference_ID', 'Is_Edited'];
        $columnsToDrop = array_filter($columns, fn ($col) => Schema::hasColumn('time_data_summary', $col));
        if (!empty($columnsToDrop)) {
            Schema::table('time_data_summary', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            if (!Schema::hasColumn('time_data_summary', 'Active')) {
                $table->tinyInteger('Active')->default(1)->after('Total_Deduction');
            }
            if (!Schema::hasColumn('time_data_summary', 'Approved')) {
                $table->tinyInteger('Approved')->default(0)->after('Encoder_ID');
            }
            if (!Schema::hasColumn('time_data_summary', 'Applied')) {
                $table->tinyInteger('Applied')->default(0)->after('Approved');
            }
            if (!Schema::hasColumn('time_data_summary', 'Reference_ID')) {
                $table->integer('Reference_ID')->nullable()->after('Applied');
            }
            if (!Schema::hasColumn('time_data_summary', 'Is_Edited')) {
                $table->tinyInteger('Is_Edited')->default(0)->after('Work_Hours');
            }
        });
    }
};
