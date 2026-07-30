<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_dpcr_outputs')) {
            return;
        }

        Schema::table('employee_dpcr_outputs', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_dpcr_outputs', 'function_type')) {
                $table->string('function_type', 20)->default('core');
                $table->index('function_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_dpcr_outputs')) {
            return;
        }

        Schema::table('employee_dpcr_outputs', function (Blueprint $table) {
            if (Schema::hasColumn('employee_dpcr_outputs', 'function_type')) {
                $table->dropColumn('function_type');
            }
        });
    }
};
