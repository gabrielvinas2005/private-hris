<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['employee_opcr_outputs', 'opcr_details'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'function_type')) {
                    $table->string('function_type', 20)->default('core');
                    $table->index('function_type');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['employee_opcr_outputs', 'opcr_details'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'function_type')) {
                    $table->dropColumn('function_type');
                }
            });
        }
    }
};
