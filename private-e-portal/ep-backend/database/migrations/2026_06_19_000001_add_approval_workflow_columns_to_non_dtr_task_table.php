<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('non_dtr_task')) {
            return;
        }

        Schema::table('non_dtr_task', function (Blueprint $table) {
            if (!Schema::hasColumn('non_dtr_task', 'approved_1')) {
                $table->boolean('approved_1')->default(0)->nullable();
                $table->unsignedBigInteger('approved_by_1_id')->default(0)->nullable();
                $table->dateTime('approved_date_1')->nullable();
                $table->boolean('disapproved_1')->default(0)->nullable();
                $table->unsignedBigInteger('disapproved_by_1_id')->default(0)->nullable();
                $table->dateTime('disapproved_date_1')->nullable();
                $table->boolean('approved_2')->default(0)->nullable();
                $table->unsignedBigInteger('approved_by_2_id')->default(0)->nullable();
                $table->dateTime('approved_date_2')->nullable();
                $table->boolean('disapproved_2')->default(0)->nullable();
                $table->unsignedBigInteger('disapproved_by_2_id')->default(0)->nullable();
                $table->dateTime('disapproved_date_2')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('non_dtr_task')) {
            return;
        }

        Schema::table('non_dtr_task', function (Blueprint $table) {
            foreach ([
                'approved_1', 'approved_by_1_id', 'approved_date_1',
                'disapproved_1', 'disapproved_by_1_id', 'disapproved_date_1',
                'approved_2', 'approved_by_2_id', 'approved_date_2',
                'disapproved_2', 'disapproved_by_2_id', 'disapproved_date_2',
            ] as $column) {
                if (Schema::hasColumn('non_dtr_task', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
