<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReworkYearendTableForSri extends Migration
{
    /**
     * Remake yearend_table based on service-length brackets:
     * - months = 0 means "less than 1 month"
     * - months = 4 means "4 months or more" (full incentive)
     */
    public function up()
    {
        Schema::dropIfExists('yearend_table');

        Schema::create('yearend_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('months')->unique();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('cash_gift', 12, 2)->default(5000);
            $table->timestamps();
        });

        DB::table('yearend_table')->insert([
            ['months' => 0, 'percentage' => 0.10, 'cash_gift' => 5000, 'created_at' => null, 'updated_at' => null], // less than 1 month
            ['months' => 1, 'percentage' => 0.20, 'cash_gift' => 5000, 'created_at' => null, 'updated_at' => null], // 1 but less than 2
            ['months' => 2, 'percentage' => 0.30, 'cash_gift' => 5000, 'created_at' => null, 'updated_at' => null], // 2 but less than 3
            ['months' => 3, 'percentage' => 0.40, 'cash_gift' => 5000, 'created_at' => null, 'updated_at' => null], // 3 but less than 4
            ['months' => 4, 'percentage' => 1.00, 'cash_gift' => 5000, 'created_at' => null, 'updated_at' => null], // 4 months or more (full)
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('yearend_table');

        Schema::create('yearend_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('months')->unique();
            $table->decimal('percentage')->default(0);
            $table->timestamps();
        });

        DB::table('yearend_table')->insert([
            ['months' => 1, 'percentage' => 0.01, 'created_at' => null, 'updated_at' => null],
            ['months' => 2, 'percentage' => 0.02, 'created_at' => null, 'updated_at' => null],
            ['months' => 3, 'percentage' => 0.03, 'created_at' => null, 'updated_at' => null],
            ['months' => 4, 'percentage' => 0.04, 'created_at' => null, 'updated_at' => null],
            ['months' => 5, 'percentage' => 0.05, 'created_at' => null, 'updated_at' => null],
            ['months' => 6, 'percentage' => 0.06, 'created_at' => null, 'updated_at' => null],
            ['months' => 7, 'percentage' => 0.07, 'created_at' => null, 'updated_at' => null],
            ['months' => 8, 'percentage' => 0.08, 'created_at' => null, 'updated_at' => null],
            ['months' => 9, 'percentage' => 0.09, 'created_at' => null, 'updated_at' => null],
            ['months' => 10, 'percentage' => 0.10, 'created_at' => null, 'updated_at' => null],
            ['months' => 11, 'percentage' => 0.11, 'created_at' => null, 'updated_at' => null],
            ['months' => 12, 'percentage' => 1.00, 'created_at' => null, 'updated_at' => null],
        ]);
    }
}

