<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $summaryAdjDecimalColumns = [
            'Hours_Worked',
            'Daily',
            'Late',
            'Late_Amount',
            'Undertime',
            'Undertime_Amount',
            'Absent',
            'Absent_Amount',
            'Total_Amount',
            'Overtime',
            'Working_Hours',
            'Work_Hours',
            'Days_Covered',
            'Total_Deduction',
        ];

        $summaryDecimalColumns = [
            'Hours_Worked',
            'Daily',
            'Late',
            'Late_Amount',
            'Undertime',
            'Undertime_Amount',
            'Absent',
            'Absent_Amount',
            'Total_Amount',
            'Overtime',
            'Working_Hours',
            'Work_Hours',
            'Days_Covered',
            'Total_Deduction',
            'Adjustment_Amount',
        ];

        $this->updateDecimalScale('time_data_summary_adj', $summaryAdjDecimalColumns, 3);
        $this->updateDecimalScale('time_data_summary', $summaryDecimalColumns, 3);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $summaryAdjDecimalColumns = [
            'Hours_Worked',
            'Daily',
            'Late',
            'Late_Amount',
            'Undertime',
            'Undertime_Amount',
            'Absent',
            'Absent_Amount',
            'Total_Amount',
            'Overtime',
            'Working_Hours',
            'Work_Hours',
            'Days_Covered',
            'Total_Deduction',
        ];

        $summaryDecimalColumns = [
            'Hours_Worked',
            'Daily',
            'Late',
            'Late_Amount',
            'Undertime',
            'Undertime_Amount',
            'Absent',
            'Absent_Amount',
            'Total_Amount',
            'Overtime',
            'Working_Hours',
            'Work_Hours',
            'Days_Covered',
            'Total_Deduction',
            'Adjustment_Amount',
        ];

        $this->updateDecimalScale('time_data_summary_adj', $summaryAdjDecimalColumns, 2);
        $this->updateDecimalScale('time_data_summary', $summaryDecimalColumns, 2);
    }

    /**
     * @param  string  $table
     * @param  array<int, string>  $columns
     * @param  int  $scale
     * @return void
     */
    private function updateDecimalScale(string $table, array $columns, int $scale): void
    {
        foreach ($columns as $column) {
            $this->alterDecimalColumnScale($table, $column, 18, $scale);
        }
    }

    /**
     * Alters the column to DECIMAL(precision, scale) while preserving NULLability.
     * Uses SQL Server ALTER COLUMN when on sqlsrv (more reliable for nullability).
     *
     * @param  string  $table
     * @param  string  $column
     * @param  int  $precision
     * @param  int  $scale
     * @return void
     */
    private function alterDecimalColumnScale(string $table, string $column, int $precision, int $scale): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            $row = DB::selectOne(
                'SELECT TABLE_SCHEMA, IS_NULLABLE
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );

            $schema = $row->TABLE_SCHEMA ?? 'dbo';
            $isNullable = strtoupper((string) ($row->IS_NULLABLE ?? 'NO')) === 'YES';
            $nullSql = $isNullable ? 'NULL' : 'NOT NULL';

            DB::statement(
                "ALTER TABLE [{$schema}].[{$table}] ALTER COLUMN [{$column}] DECIMAL({$precision},{$scale}) {$nullSql}"
            );

            return;
        }

        // Fallback for other drivers (doctrine/dbal is already required in this repo).
        Schema::table($table, function (Blueprint $blueprint) use ($column, $precision, $scale) {
            $blueprint->decimal($column, $precision, $scale)->change();
        });
    }
};

