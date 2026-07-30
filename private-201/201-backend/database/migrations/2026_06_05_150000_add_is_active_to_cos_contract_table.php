<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToCosContractTable extends Migration
{
    public function up()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            if (!Schema::hasColumn('cos_contract', 'is_active')) {
                $table->boolean('is_active')->default(false);
            }
        });

        $employeeIds = DB::table('cos_contract')->distinct()->pluck('employee_id');

        foreach ($employeeIds as $employeeId) {
            $contracts = DB::table('cos_contract')
                ->where('employee_id', $employeeId)
                ->orderByDesc('Start_date')
                ->orderByDesc('id')
                ->get();

            if ($contracts->isEmpty()) {
                continue;
            }

            $today = Carbon::today();
            $activeId = null;

            foreach ($contracts as $contract) {
                if ($this->isContractActiveOnDate($contract, $today)) {
                    $activeId = $contract->id;
                    break;
                }
            }

            if (!$activeId) {
                $activeId = $contracts->first()->id;
            }

            DB::table('cos_contract')
                ->where('employee_id', $employeeId)
                ->update(['is_active' => false]);

            DB::table('cos_contract')
                ->where('id', $activeId)
                ->update(['is_active' => true]);
        }
    }

    public function down()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            if (Schema::hasColumn('cos_contract', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    private function isContractActiveOnDate(object $contract, Carbon $date): bool
    {
        if (!empty($contract->End_date)) {
            $endDate = Carbon::parse($contract->End_date)->startOfDay();
            if ($endDate->lt($date)) {
                return false;
            }
        }

        if (!empty($contract->Start_date)) {
            $startDate = Carbon::parse($contract->Start_date)->startOfDay();
            if ($startDate->gt($date)) {
                return false;
            }
        }

        return !empty($contract->Start_date) || !empty($contract->End_date);
    }
}
