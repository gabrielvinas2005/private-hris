<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CosPayrollHoldService
{
    public const AUTO_HOLD_REMARK = 'Automatic hold: No active COS contract or contract end date has passed.';

    public function hasActiveContract(int $employeeId): bool
    {
        return $this->getActiveContract($employeeId) !== null;
    }

    public function getActiveContract(int $employeeId): ?object
    {
        $contracts = DB::table('cos_contract')
            ->where('employee_id', $employeeId)
            ->orderByDesc('Start_date')
            ->orderByDesc('id')
            ->get();

        if ($contracts->isEmpty()) {
            return null;
        }

        $today = Carbon::today();

        foreach ($contracts as $contract) {
            if ($this->isContractActiveOnDate($contract, $today)) {
                return $contract;
            }
        }

        return null;
    }

    public function syncContractActiveStatus(int $employeeId): void
    {
        $contracts = DB::table('cos_contract')
            ->where('employee_id', $employeeId)
            ->orderByDesc('Start_date')
            ->orderByDesc('id')
            ->get();

        if ($contracts->isEmpty()) {
            return;
        }

        $today = Carbon::today();
        $activeContract = null;

        foreach ($contracts as $contract) {
            if ($this->isContractActiveOnDate($contract, $today)) {
                $activeContract = $contract;
                break;
            }
        }

        DB::table('cos_contract')
            ->where('employee_id', $employeeId)
            ->update(['is_active' => 0]);

        if ($activeContract) {
            DB::table('cos_contract')
                ->where('id', $activeContract->id)
                ->update(['is_active' => 1]);
        }
    }

    public function syncEmployeeSalaryFromActiveContract(int $employeeId): void
    {
        $employee = DB::table('employees')
            ->where('id', $employeeId)
            ->first(['employment_type_id']);

        if (!$employee || (int) $employee->employment_type_id !== 2) {
            return;
        }

        $activeContract = $this->getActiveContract($employeeId);
        if (!$activeContract) {
            return;
        }

        DB::table('employees')->where('id', $employeeId)->update([
            'salary' => $activeContract->salary ?? 0,
            'salary_grade_id' => $activeContract->salary_grade_id ?? 0,
            'salary_step_id' => $activeContract->salary_step_id ?? 0,
            'updated_at' => now(),
        ]);
    }

    public function syncEmployee(int $employeeId): void
    {
        $employee = DB::table('employees')
            ->where('id', $employeeId)
            ->first(['employment_type_id', 'is_hold', 'hold_remarks']);

        if (!$employee || (int) $employee->employment_type_id !== 2) {
            return;
        }

        if (!$this->hasActiveContract($employeeId)) {
            DB::table('employees')->where('id', $employeeId)->update([
                'is_hold' => true,
                'hold_remarks' => self::AUTO_HOLD_REMARK,
                'updated_at' => now(),
            ]);

            return;
        }

        if (
            (bool) $employee->is_hold
            && trim((string) ($employee->hold_remarks ?? '')) === self::AUTO_HOLD_REMARK
        ) {
            DB::table('employees')->where('id', $employeeId)->update([
                'is_hold' => false,
                'hold_remarks' => '',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array{total:int, held:int, released:int}
     */
    public function syncAllCosEmployees(): array
    {
        $employeeIds = DB::table('employees')
            ->where('employment_type_id', 2)
            ->where('active', true)
            ->pluck('id');

        $held = 0;
        $released = 0;
        $alreadyHeld = 0;
        $alreadyActive = 0;

        foreach ($employeeIds as $employeeId) {
            $this->syncContractActiveStatus((int) $employeeId);
            $before = (bool) DB::table('employees')->where('id', $employeeId)->value('is_hold');
            $this->syncEmployee((int) $employeeId);
            $after = (bool) DB::table('employees')->where('id', $employeeId)->value('is_hold');

            if (!$before && $after) {
                $held++;
            } elseif ($before && !$after) {
                $released++;
            } elseif ($after) {
                $alreadyHeld++;
            } else {
                $alreadyActive++;
            }
        }

        return [
            'total' => $employeeIds->count(),
            'held' => $held,
            'released' => $released,
            'already_held' => $alreadyHeld,
            'already_active' => $alreadyActive,
            'with_contract' => DB::table('cos_contract')->distinct('employee_id')->count('employee_id'),
        ];
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
