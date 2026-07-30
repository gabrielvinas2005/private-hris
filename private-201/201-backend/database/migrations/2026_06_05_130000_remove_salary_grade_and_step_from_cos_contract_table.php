<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSalaryGradeAndStepFromCosContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('cos_contract', 'salary_grade_id') ? 'salary_grade_id' : null,
                Schema::hasColumn('cos_contract', 'salary_step_id') ? 'salary_step_id' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            if (!Schema::hasColumn('cos_contract', 'salary_grade_id')) {
                $table->unsignedBigInteger('salary_grade_id')->nullable()->default(0);
            }
            if (!Schema::hasColumn('cos_contract', 'salary_step_id')) {
                $table->unsignedBigInteger('salary_step_id')->nullable()->default(0);
            }
        });
    }
}
