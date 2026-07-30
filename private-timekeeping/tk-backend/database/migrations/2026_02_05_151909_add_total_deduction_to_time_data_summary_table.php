<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTotalDeductionToTimeDataSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data_summary', function (Blueprint $table) {
            // Add Total_Deduction column (sum of Absent_Amount + Late_Amount + Undertime_Amount)
            $table->decimal('Total_Deduction', 18, 2)->nullable()->after('Absent_Amount');
        });

        // Calculate and populate Total_Deduction for existing records
        DB::statement('UPDATE time_data_summary SET Total_Deduction = ISNULL(Absent_Amount, 0) + ISNULL(Late_Amount, 0) + ISNULL(Undertime_Amount, 0)');

        // Drop unused columns (check existence first to avoid errors)
        $columnsToDrop = [
            'Annual_Salary',
            'Late_AM',
            'Late_PM',
            'Late_Frequency',
            'Late_Fraction',
            'Undertime_AM',
            'Undertime_PM',
            'Undertime_Frequency',
            'Undertime_Fraction',
            'COLA',
            'SEA',
            'ND',
            'ND_Amount',
            'OTND',
            'OTND_Amount',
            'Company_ID',
            'Is_Taxable',
            'ROT',
            'RDOT',
            'SHOT',
            'SHRDOT',
            'LHOT',
            'DHOT',
            'DHRDOT',
            'LHRDOT',
            'Restday',
            'Legal_Holiday',
            'Special_Holiday',
            'ROT_Amount',
            'RDOT_Amount',
            'SHOT_Amount',
            'SHRDOT_Amount',
            'LHOT_Amount',
            'DHOT_Amount',
            'DHRDOT_Amount',
            'LHRDOT_Amount',
            'Restday_Amount',
            'Legal_Amount',
            'Special_Amount',
            'Branch_ID'
        ];

        // Drop columns in batches (SQL Server has limits on number of columns per ALTER TABLE)
        $batches = array_chunk($columnsToDrop, 10);
        foreach ($batches as $batch) {
            $existingColumns = [];
            foreach ($batch as $column) {
                if (Schema::hasColumn('time_data_summary', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            if (!empty($existingColumns)) {
                Schema::table('time_data_summary', function (Blueprint $table) use ($existingColumns) {
                    $table->dropColumn($existingColumns);
                });
            }
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
            // Re-add dropped columns (nullable, as they were set to NULL)
            $table->decimal('Annual_Salary', 18, 2)->nullable();
            $table->decimal('Late_AM', 18, 2)->nullable();
            $table->decimal('Late_PM', 18, 2)->nullable();
            $table->integer('Late_Frequency')->nullable();
            $table->decimal('Late_Fraction', 18, 2)->nullable();
            $table->decimal('Undertime_AM', 18, 2)->nullable();
            $table->decimal('Undertime_PM', 18, 2)->nullable();
            $table->integer('Undertime_Frequency')->nullable();
            $table->decimal('Undertime_Fraction', 18, 2)->nullable();
            $table->decimal('COLA', 18, 2)->nullable();
            $table->decimal('SEA', 18, 2)->nullable();
            $table->decimal('ND', 18, 2)->nullable();
            $table->decimal('ND_Amount', 18, 2)->nullable();
            $table->decimal('OTND', 18, 2)->nullable();
            $table->decimal('OTND_Amount', 18, 2)->nullable();
            $table->integer('Company_ID')->nullable();
            $table->bit('Is_Taxable')->nullable();
            $table->decimal('ROT', 18, 2)->nullable();
            $table->decimal('RDOT', 18, 2)->nullable();
            $table->decimal('SHOT', 18, 2)->nullable();
            $table->decimal('SHRDOT', 18, 2)->nullable();
            $table->decimal('LHOT', 18, 2)->nullable();
            $table->decimal('DHOT', 18, 2)->nullable();
            $table->decimal('DHRDOT', 18, 2)->nullable();
            $table->decimal('LHRDOT', 18, 2)->nullable();
            $table->decimal('Restday', 18, 2)->nullable();
            $table->decimal('Legal_Holiday', 18, 2)->nullable();
            $table->decimal('Special_Holiday', 18, 2)->nullable();
            $table->decimal('ROT_Amount', 18, 2)->nullable();
            $table->decimal('RDOT_Amount', 18, 2)->nullable();
            $table->decimal('SHOT_Amount', 18, 2)->nullable();
            $table->decimal('SHRDOT_Amount', 18, 2)->nullable();
            $table->decimal('LHOT_Amount', 18, 2)->nullable();
            $table->decimal('DHOT_Amount', 18, 2)->nullable();
            $table->decimal('DHRDOT_Amount', 18, 2)->nullable();
            $table->decimal('LHRDOT_Amount', 18, 2)->nullable();
            $table->decimal('Restday_Amount', 18, 2)->nullable();
            $table->decimal('Legal_Amount', 18, 2)->nullable();
            $table->decimal('Special_Amount', 18, 2)->nullable();
            $table->integer('Branch_ID')->nullable();
        });

        Schema::table('time_data_summary', function (Blueprint $table) {
            // Remove Total_Deduction column
            $table->dropColumn('Total_Deduction');
        });
    }
}
