<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterPayrollSummariesDecimalColumnsToPreventOverflow extends Migration
{
    /**
     * Run the migrations.
     * Changes decimal(8,2) columns to decimal(18,2) to prevent arithmetic overflow
     * when total_deduction or net_pay exceed 999,999.99.
     *
     * @return void
     */
    public function up()
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $columns = [
            'salary', 'gsis', 'sss', 'pagibig', 'philhealth', 'tax',
            'late_amount', 'ut_amount', 'absent_amount', 'holiday_amount',
            'ot_amount', 'nd_amount', 'total_income', 'total_deduction',
            'gross_amount', 'net_pay'
        ];

        if ($driver === 'sqlsrv') {
            foreach ($columns as $col) {
                DB::statement("ALTER TABLE payroll_summaries ALTER COLUMN [{$col}] decimal(18,2) NULL");
            }
        } else {
            Schema::table('payroll_summaries', function (Blueprint $table) use ($columns) {
                foreach ($columns as $col) {
                    $table->decimal($col, 18, 2)->nullable()->change();
                }
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
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $columns = [
            'salary', 'gsis', 'sss', 'pagibig', 'philhealth', 'tax',
            'late_amount', 'ut_amount', 'absent_amount', 'holiday_amount',
            'ot_amount', 'nd_amount', 'total_income', 'total_deduction',
            'gross_amount', 'net_pay'
        ];

        if ($driver === 'sqlsrv') {
            foreach ($columns as $col) {
                DB::statement("ALTER TABLE payroll_summaries ALTER COLUMN [{$col}] decimal(8,2) NULL");
            }
        } else {
            Schema::table('payroll_summaries', function (Blueprint $table) use ($columns) {
                foreach ($columns as $col) {
                    $table->decimal($col, 8, 2)->nullable()->change();
                }
            });
        }
    }
}
