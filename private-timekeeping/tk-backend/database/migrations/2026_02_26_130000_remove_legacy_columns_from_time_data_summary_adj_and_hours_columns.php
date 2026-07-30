<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * time_data_summary_adj: Remove Payroll_Period_ID (use Preceding_Payroll_Period_ID only), move
     * Preceding_Payroll_Period_ID after Time_Data_Summary_Adj_ID, and remove Approved, Applied,
     * Reference_ID, Working_Hours, Work_Hours, Is_Edited.
     *
     * time_data_summary & time_data_summary_adj: Remove Working_Hours and Work_Hours (redundant with Hours_Worked).
     *
     * @return void
     */
    public function up()
    {
        // ---------- time_data_summary_adj ----------
        $adjDrops = ['Payroll_Period_ID', 'Approved', 'Applied', 'Reference_ID', 'Working_Hours', 'Work_Hours', 'Is_Edited'];
        $adjToDrop = array_filter($adjDrops, fn ($col) => Schema::hasColumn('time_data_summary_adj', $col));
        if (!empty($adjToDrop)) {
            Schema::table('time_data_summary_adj', function (Blueprint $table) use ($adjToDrop) {
                $table->dropColumn($adjToDrop);
            });
        }

        // Move Preceding_Payroll_Period_ID to after Time_Data_Summary_Adj_ID (preserves data; MySQL/MariaDB)
        if (Schema::hasColumn('time_data_summary_adj', 'Preceding_Payroll_Period_ID')) {
            $driver = Schema::getConnection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::statement('ALTER TABLE time_data_summary_adj MODIFY COLUMN Preceding_Payroll_Period_ID INT NULL COMMENT \'The preceding period this adjustment comes from\' AFTER Time_Data_Summary_Adj_ID');
            }
        }

        // ---------- time_data_summary: drop Working_Hours, Work_Hours ----------
        $summaryHoursDrops = ['Working_Hours', 'Work_Hours'];
        $summaryToDrop = array_filter($summaryHoursDrops, fn ($col) => Schema::hasColumn('time_data_summary', $col));
        if (!empty($summaryToDrop)) {
            Schema::table('time_data_summary', function (Blueprint $table) use ($summaryToDrop) {
                $table->dropColumn($summaryToDrop);
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
        // time_data_summary: re-add Working_Hours, Work_Hours
        if (!Schema::hasColumn('time_data_summary', 'Working_Hours')) {
            Schema::table('time_data_summary', function (Blueprint $table) {
                $table->decimal('Working_Hours', 18, 2)->default(0.00)->after('Hours_Worked');
            });
        }
        if (!Schema::hasColumn('time_data_summary', 'Work_Hours')) {
            Schema::table('time_data_summary', function (Blueprint $table) {
                $table->decimal('Work_Hours', 18, 2)->default(0.00)->after('Working_Hours');
            });
        }

        // time_data_summary_adj: re-add dropped columns (Preceding_Payroll_Period_ID stays after ID)
        Schema::table('time_data_summary_adj', function (Blueprint $table) {
            if (!Schema::hasColumn('time_data_summary_adj', 'Payroll_Period_ID')) {
                $table->integer('Payroll_Period_ID')->nullable()->after('Preceding_Payroll_Period_ID');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Approved')) {
                $table->tinyInteger('Approved')->default(0)->after('Encoder_ID');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Applied')) {
                $table->tinyInteger('Applied')->default(0)->after('Approved');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Reference_ID')) {
                $table->integer('Reference_ID')->nullable()->after('Applied');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Working_Hours')) {
                $table->decimal('Working_Hours', 18, 2)->default(0.00)->after('Reference_ID');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Work_Hours')) {
                $table->decimal('Work_Hours', 18, 2)->default(0.00)->after('Working_Hours');
            }
            if (!Schema::hasColumn('time_data_summary_adj', 'Is_Edited')) {
                $table->tinyInteger('Is_Edited')->default(0)->after('Work_Hours');
            }
        });
    }
};
