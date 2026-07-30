<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Sums preceding adjustment (time_data_summary.Adjustment_Amount) for every payroll
 * period in the same calendar month as the anchor period (semi-monthly: 1st + 2nd half).
 */
class PayslipPrecedingTotals
{
    /**
     * @return array{preceding_late_amount: float, preceding_ut_amount: float, preceding_absent_amount: float, preceding_deduction_total: float}
     */
    public static function fromTimeDataSummary(int $employeeId, int $payrollPeriodId): array
    {
        $pp = DB::table('payroll_periods')->where('id', $payrollPeriodId)->first();
        if (!$pp) {
            return self::emptyTotals();
        }

        $release = $pp->release_date ?? null;
        $monthIds = [$payrollPeriodId];
        if ($release) {
            $y = (int) date('Y', strtotime($release));
            $m = (int) date('n', strtotime($release));
            $monthIds = DB::table('payroll_periods')
                ->where('payroll_interval_id', $pp->payroll_interval_id)
                ->whereYear('release_date', $y)
                ->whereMonth('release_date', $m)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            if ($monthIds === []) {
                $monthIds = [$payrollPeriodId];
            }
        }

        $agg = DB::table('time_data_summary')
            ->where('employee_id', $employeeId)
            ->whereIn('payroll_period_id', $monthIds)
            ->select(
                DB::raw('SUM(ISNULL(Adjustment_Amount, 0)) as preceding_deduction_total')
            )
            ->first();

        if (!$agg) {
            return self::emptyTotals();
        }

        $total = (float) ($agg->preceding_deduction_total ?? 0);

        return [
            'preceding_late_amount' => 0.0,
            'preceding_ut_amount' => 0.0,
            'preceding_absent_amount' => 0.0,
            'preceding_deduction_total' => $total,
        ];
    }

    private static function emptyTotals(): array
    {
        return [
            'preceding_late_amount' => 0.0,
            'preceding_ut_amount' => 0.0,
            'preceding_absent_amount' => 0.0,
            'preceding_deduction_total' => 0.0,
        ];
    }
}
