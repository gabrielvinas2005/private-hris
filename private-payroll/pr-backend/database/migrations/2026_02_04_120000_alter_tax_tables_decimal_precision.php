<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterTaxTablesDecimalPrecision extends Migration
{
    /**
     * Run the migrations.
     * Fixes arithmetic overflow: original columns were decimal(8,2) (max 999,999.99).
     * TRAIN brackets need max_amount up to 999,999,999 and base_tax up to 2,202,500.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [percentage] decimal(5,4) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [min_amount] decimal(18,2) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [max_amount] decimal(18,2) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [base_tax] decimal(18,2) NOT NULL');
        } else {
            Schema::table('tax_tables', function (Blueprint $table) {
                $table->decimal('percentage', 5, 4)->default(0)->change();
                $table->decimal('min_amount', 18, 2)->default(0)->change();
                $table->decimal('max_amount', 18, 2)->default(0)->change();
                $table->decimal('base_tax', 18, 2)->default(0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [percentage] decimal(8,2) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [min_amount] decimal(8,2) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [max_amount] decimal(8,2) NOT NULL');
            DB::statement('ALTER TABLE tax_tables ALTER COLUMN [base_tax] decimal(8,2) NOT NULL');
        } else {
            Schema::table('tax_tables', function (Blueprint $table) {
                $table->decimal('percentage', 8, 2)->default(0)->change();
                $table->decimal('min_amount', 8, 2)->default(0)->change();
                $table->decimal('max_amount', 8, 2)->default(0)->change();
                $table->decimal('base_tax', 8, 2)->default(0)->change();
            });
        }
    }
}
