<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_process_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_process_runs', 'phase')) {
                $table->string('phase', 32)->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'current_date')) {
                $table->date('current_date')->nullable()->after('phase');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'current_date_index')) {
                $table->integer('current_date_index')->nullable()->after('current_date');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'total_dates')) {
                $table->integer('total_dates')->nullable()->after('current_date_index');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'current_employee_index')) {
                $table->integer('current_employee_index')->nullable()->after('total_dates');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'total_employees')) {
                $table->integer('total_employees')->nullable()->after('current_employee_index');
            }
            if (!Schema::hasColumn('attendance_process_runs', 'progress_message')) {
                $table->string('progress_message', 255)->nullable()->after('total_employees');
            }
        });
    }

    public function down()
    {
        Schema::table('attendance_process_runs', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'phase',
                'current_date',
                'current_date_index',
                'total_dates',
                'current_employee_index',
                'total_employees',
                'progress_message',
            ] as $col) {
                if (Schema::hasColumn('attendance_process_runs', $col)) {
                    $dropColumns[] = $col;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

