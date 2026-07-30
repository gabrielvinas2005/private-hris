<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategorySummaryToEmployeeOpcrTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employee_opcr')) {
            return;
        }

        Schema::table('employee_opcr', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_opcr', 'strategic_mfo')) {
                $table->decimal('strategic_mfo', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('employee_opcr', 'strategic_rating')) {
                $table->decimal('strategic_rating', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('employee_opcr', 'core_mfo')) {
                $table->decimal('core_mfo', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('employee_opcr', 'core_rating')) {
                $table->decimal('core_rating', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('employee_opcr', 'support_mfo')) {
                $table->decimal('support_mfo', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('employee_opcr', 'support_rating')) {
                $table->decimal('support_rating', 5, 2)->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('employee_opcr')) {
            return;
        }

        Schema::table('employee_opcr', function (Blueprint $table) {
            $columns = [
                'strategic_mfo',
                'strategic_rating',
                'core_mfo',
                'core_rating',
                'support_mfo',
                'support_rating',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('employee_opcr', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
