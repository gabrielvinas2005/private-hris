<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeAverageRatingToDecimalOnEmployeeIpcrOutputsTable extends Migration
{
    public function up()
    {
        $this->alterAverageRatingToDecimal('employee_ipcr_outputs', false);
        $this->alterAverageRatingToDecimal('ipcr_recalibrations', true);
    }

    public function down()
    {
        $this->alterAverageRatingToInteger('employee_ipcr_outputs', false);
        $this->alterAverageRatingToInteger('ipcr_recalibrations', true);
    }

    private function alterAverageRatingToDecimal(string $table, bool $nullable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'average_rating')) {
            return;
        }

        $this->dropDefaultConstraint($table, 'average_rating');

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE {$table} ALTER COLUMN average_rating DECIMAL(5,2) {$nullSql}");

        if (!$nullable) {
            DB::statement("
                IF NOT EXISTS (
                    SELECT 1
                    FROM sys.default_constraints dc
                    INNER JOIN sys.columns c ON c.default_object_id = dc.object_id
                    INNER JOIN sys.tables t ON t.object_id = c.object_id
                    WHERE t.name = '{$table}' AND c.name = 'average_rating'
                )
                ALTER TABLE {$table} ADD CONSTRAINT DF_{$table}_average_rating DEFAULT 1 FOR average_rating
            ");
        }
    }

    private function alterAverageRatingToInteger(string $table, bool $nullable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'average_rating')) {
            return;
        }

        $this->dropDefaultConstraint($table, 'average_rating');

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE {$table} ALTER COLUMN average_rating INT {$nullSql}");

        if (!$nullable) {
            DB::statement("
                IF NOT EXISTS (
                    SELECT 1
                    FROM sys.default_constraints dc
                    INNER JOIN sys.columns c ON c.default_object_id = dc.object_id
                    INNER JOIN sys.tables t ON t.object_id = c.object_id
                    WHERE t.name = '{$table}' AND c.name = 'average_rating'
                )
                ALTER TABLE {$table} ADD CONSTRAINT DF_{$table}_average_rating DEFAULT 1 FOR average_rating
            ");
        }
    }

    private function dropDefaultConstraint(string $table, string $column): void
    {
        DB::statement("
            DECLARE @constraint NVARCHAR(200);
            SELECT @constraint = dc.name
            FROM sys.default_constraints dc
            INNER JOIN sys.columns c ON c.default_object_id = dc.object_id
            INNER JOIN sys.tables t ON t.object_id = c.object_id
            WHERE t.name = '{$table}' AND c.name = '{$column}';

            IF @constraint IS NOT NULL
                EXEC('ALTER TABLE {$table} DROP CONSTRAINT ' + @constraint);
        ");
    }
}
