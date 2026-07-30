<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('dpcr_recalibrations')) {
            return;
        }

        Schema::table('dpcr_recalibrations', function (Blueprint $table) {
            if (!Schema::hasColumn('dpcr_recalibrations', 'employee_dpcr_output_id')) {
                // Link recalibration row to a specific employee_dpcr_outputs.id
                $table->bigInteger('employee_dpcr_output_id')->nullable()->index();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('dpcr_recalibrations')) {
            return;
        }

        Schema::table('dpcr_recalibrations', function (Blueprint $table) {
            if (Schema::hasColumn('dpcr_recalibrations', 'employee_dpcr_output_id')) {
                $table->dropColumn('employee_dpcr_output_id');
            }
        });
    }
};
