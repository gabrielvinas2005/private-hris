<?php

namespace App\Helpers;

use Carbon\Carbon;

class Time_Calculation
{
    /**
     * MinutesToDayFraction lookup (minutes => day_fraction).
     * Matches database MinutesToDayFraction table and frontend useDayFractionConversion.js.
     */
    private const MINUTES_TO_DAY_FRACTION = [
        0 => 0.000, 1 => 0.002, 2 => 0.004, 3 => 0.006, 4 => 0.008, 5 => 0.010,
        6 => 0.012, 7 => 0.015, 8 => 0.017, 9 => 0.019, 10 => 0.021, 11 => 0.023,
        12 => 0.025, 13 => 0.027, 14 => 0.029, 15 => 0.031, 16 => 0.033, 17 => 0.035,
        18 => 0.037, 19 => 0.040, 20 => 0.042, 21 => 0.044, 22 => 0.046, 23 => 0.048,
        24 => 0.050, 25 => 0.052, 26 => 0.054, 27 => 0.056, 28 => 0.058, 29 => 0.060,
        30 => 0.062, 31 => 0.065, 32 => 0.067, 33 => 0.069, 34 => 0.071, 35 => 0.073,
        36 => 0.075, 37 => 0.077, 38 => 0.079, 39 => 0.081, 40 => 0.083, 41 => 0.085,
        42 => 0.087, 43 => 0.090, 44 => 0.092, 45 => 0.094, 46 => 0.096, 47 => 0.098,
        48 => 0.100, 49 => 0.102, 50 => 0.104, 51 => 0.106, 52 => 0.108, 53 => 0.110,
        54 => 0.112, 55 => 0.115, 56 => 0.117, 57 => 0.119, 58 => 0.121, 59 => 0.123,
    ];

    /**
     * Resolve remaining day-fraction (after whole hours) to minutes using static lookup.
     */
    private static function minutesFromRemainingFraction(float $remainingFraction): int
    {
        if ($remainingFraction <= 0.0001) {
            return 0;
        }

        $fractionToMinutes = [];
        foreach (self::MINUTES_TO_DAY_FRACTION as $mins => $frac) {
            $fractionToMinutes[(string) round($frac, 3)] = (int) $mins;
        }

        $fractions = array_map('floatval', array_keys($fractionToMinutes));
        sort($fractions);

        foreach ($fractions as $fraction) {
            if (abs($remainingFraction - $fraction) <= 0.001) {
                return $fractionToMinutes[(string) round($fraction, 3)] ?? 0;
            }
        }

        $closestFraction = $fractions[0];
        $minDiff = abs($remainingFraction - $closestFraction);
        foreach ($fractions as $fraction) {
            $diff = abs($remainingFraction - $fraction);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closestFraction = $fraction;
            }
        }

        return $fractionToMinutes[(string) round($closestFraction, 3)] ?? 0;
    }

    /**
     * Resolve minutes portion of a day fraction from the static lookup table.
     */
    private static function minuteFractionFromLookup(int $minutes): float
    {
        return (float) (self::MINUTES_TO_DAY_FRACTION[$minutes] ?? 0);
    }
    /**
     * Parse a time string (HH:mm or HH:mm:ss) into minutes since midnight.
     */
    public static function parseToMinutes($time)
    {
        if ($time === null || $time === '') return null;
        $timeStr = (string) $time;
        try {
            $fmt = strlen($timeStr) === 5 ? 'H:i' : 'H:i:s';
            $c = Carbon::createFromFormat($fmt, $timeStr);
            return $c->hour * 60 + $c->minute; // ignore seconds for simplicity
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Compute difference in minutes from start to end, supporting cross-midnight.
     */
    public static function diffMinutes(?int $startMinutes, ?int $endMinutes): int
    {
        if ($startMinutes === null || $endMinutes === null) return 0;
        if ($endMinutes >= $startMinutes) return $endMinutes - $startMinutes;
        // cross midnight
        return $endMinutes + 24 * 60 - $startMinutes;
    }

    /**
     * Compute scheduled work minutes using schedule times and an optional break interval.
     */
    public static function computeScheduledWorkMinutes($amIn, $breakIn, $breakOut, $pmOut): int
    {
        $amInM     = self::parseToMinutes($amIn);
        $breakInM  = self::parseToMinutes($breakIn);
        $breakOutM = self::parseToMinutes($breakOut);
        $pmOutM    = self::parseToMinutes($pmOut);

        $morning   = self::diffMinutes($amInM, $breakInM ?: $pmOutM);
        $afternoon = self::diffMinutes($breakOutM ?: $amInM, $pmOutM);

        // Subtract break segment if both provided
        $breakSeg  = ($breakInM !== null && $breakOutM !== null) ? self::diffMinutes($breakInM, $breakOutM) : 0;

        $total = max(0, $morning + $afternoon - $breakSeg);
        return $total;
    }

    /**
     * Compute late minutes for a non-flexi schedule.
     */
    public static function computeLateMinutes($actualAmIn, $scheduledAmIn, int $gracePeriodMinutes = 0, bool $isFlexi = false): int
    {
        if ($isFlexi) return 0; // flexi employees are not penalized by late if allowed
        $act = self::parseToMinutes($actualAmIn);
        $sch = self::parseToMinutes($scheduledAmIn);
        if ($act === null || $sch === null) return 0;
        $late = max(0, $act - $sch - max(0, $gracePeriodMinutes));
        return $late;
    }

    /**
     * Compute undertime minutes for a non-flexi schedule.
     */
    public static function computeUndertimeMinutes($actualPmOut, $scheduledPmOut, bool $isFlexi = false): int
    {
        if ($isFlexi) return 0;
        $act = self::parseToMinutes($actualPmOut);
        $sch = self::parseToMinutes($scheduledPmOut);
        if ($act === null || $sch === null) return 0;
        $under = max(0, $sch - $act);
        return $under;
    }

    /**
     * Compute late and undertime minutes for a flexi schedule.
     * Matches SP_ProcessTimeData flexi branch (AdjustedExpectedPmOut logic).
     *
     * @return array{late_minutes: int, undertime_minutes: int, total_minutes: int}
     */
    public static function computeFlexiTardinessMinutes(
        $actualAmIn,
        $actualPmOut,
        $scheduledAmIn,
        $scheduledPmOut,
        float $flexiHours = 0,
        float $gracePeriodMinutes = 0,
        float $scheduledWorkHours = 8.0,
        int $lunchBreakMinutes = 60
    ): array {
        if ($flexiHours <= 0) {
            return ['late_minutes' => 0, 'undertime_minutes' => 0, 'total_minutes' => 0];
        }

        $schedAm = self::parseToMinutes($scheduledAmIn);
        $schedPm = self::parseToMinutes($scheduledPmOut);
        $actualAm = self::parseToMinutes($actualAmIn);
        $actualPm = self::parseToMinutes($actualPmOut);

        if ($schedAm === null || $schedPm === null || $actualAm === null || $actualPm === null) {
            return ['late_minutes' => 0, 'undertime_minutes' => 0, 'total_minutes' => 0];
        }

        $flexiMinutes = (int) round($flexiHours * 60);
        $graceMinutes = (int) round($gracePeriodMinutes);
        $workMinutes = (int) round(max(0, $scheduledWorkHours) * 60);
        $lunchMinutes = max(0, $lunchBreakMinutes);

        // Minutes after scheduled AM in — must be compensated by extending PM out (flexi rule).
        $actualLateMinutes = max(0, $actualAm - $schedAm);
        $flexiLatestTimeIn = $schedAm + $graceMinutes + $flexiMinutes;
        $flexiLatestTimeOut = $schedPm + $flexiMinutes;

        // Expected out: scheduled PM out + minutes late from scheduled AM in (flexi compensation on time-out).
        $shiftedExpectedPmOut = min($schedPm + $actualLateMinutes, $flexiLatestTimeOut);
        // Also require full work hours (+ lunch) counted from actual time-in.
        $completeDayExpectedPmOut = min($actualAm + $workMinutes + $lunchMinutes, $flexiLatestTimeOut);
        // Use the later requirement so flexi used on arrival is always compensated on departure.
        $requiredPmOut = max($shiftedExpectedPmOut, $completeDayExpectedPmOut);

        $lateMinutes = 0;
        if ($actualAm > $flexiLatestTimeIn) {
            $lateMinutes = $actualAm - $flexiLatestTimeIn;
        }

        $undertimeMinutes = 0;
        if ($actualPm < $requiredPmOut) {
            $undertimeMinutes = $requiredPmOut - $actualPm;
        }

        return [
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'total_minutes' => $lateMinutes + $undertimeMinutes,
        ];
    }

    /**
     * Compute combined tardiness from clock times when stored day fractions are zero.
     *
     * @return array{late_minutes: int, undertime_minutes: int, total_minutes: int}
     */
    public static function computeTardinessFromScheduleTimes(
        $actualAmIn,
        $actualPmOut,
        $scheduledAmIn,
        $scheduledPmOut,
        float $flexiHours = 0,
        float $gracePeriodMinutes = 0,
        float $scheduledWorkHours = 8.0,
        int $lunchBreakMinutes = 60
    ): array {
        if ($flexiHours > 0) {
            return self::computeFlexiTardinessMinutes(
                $actualAmIn,
                $actualPmOut,
                $scheduledAmIn,
                $scheduledPmOut,
                $flexiHours,
                $gracePeriodMinutes,
                $scheduledWorkHours,
                $lunchBreakMinutes
            );
        }

        $lateMinutes = self::computeLateMinutes(
            $actualAmIn,
            $scheduledAmIn,
            (int) round($gracePeriodMinutes),
            false
        );
        $undertimeMinutes = self::computeUndertimeMinutes($actualPmOut, $scheduledPmOut, false);

        return [
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'total_minutes' => $lateMinutes + $undertimeMinutes,
        ];
    }

    /**
     * Given scheduled work minutes and penalties, compute final working minutes.
     */
    public static function computeWorkingMinutes(int $scheduledWorkMinutes, int $lateMinutes, int $undertimeMinutes): int
    {
        $work = $scheduledWorkMinutes - max(0, $lateMinutes) - max(0, $undertimeMinutes);
        return max(0, $work);
    }

    /**
     * Convert minutes to hour-minute decimal notation with two decimals (e.g., 7.36 for 7h36m).
     * NOTE: This is not decimal hours; it's HH.MM where MM is minutes 00-59.
     */
    public static function minutesToHourMinuteDecimal(int $minutes): float
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        return (float) sprintf('%d.%02d', $hours, $mins);
    }

    /**
     * Convert hour-minute decimal notation (e.g., 7.36) back to minutes.
     */
    public static function hourMinuteDecimalToMinutes($value): int
    {
        if ($value === null) return 0;
        $str = sprintf('%.2f', (float) $value);
        if (strpos($str, '.') === false) return ((int) $str) * 60;
        [$h, $m] = explode('.', $str, 2);
        $hours = (int) $h;
        $mins = (int) $m; // already two-digit minutes
        if ($mins > 59) $mins = 59;
        return $hours * 60 + $mins;
    }

    /**
     * Human-friendly formatter like "7 hrs and 36 mins" from minutes.
     */
    public static function formatHuman(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        $hLabel = $hours === 1 ? 'hr' : 'hrs';
        $mLabel = $mins === 1 ? 'min' : 'mins';
        if ($hours > 0 && $mins > 0) return "$hours $hLabel and $mins $mLabel";
        if ($hours > 0) return "$hours $hLabel";
        return "$mins $mLabel";
    }

    /**
     * Convert day fraction (based on 8-hour workday) to hours and minutes.
     * Uses the MinutesToDayFraction lookup table to match database function fn_MinutesToDayFraction.
     * 
     * @param float $dayFraction The day fraction value (e.g., 0.9810 = 7 hours 51 minutes)
     * @return array ['hours' => int, 'minutes' => int, 'total_minutes' => int]
     */
    public static function dayFractionToHoursMinutes(float $dayFraction): array
    {

        // Handle negative or zero values
        if ($dayFraction <= 0) {
            return ['hours' => 0, 'minutes' => 0, 'total_minutes' => 0];
        }

        // Round to 3 decimal places to match database precision
        $dayFraction = round($dayFraction, 3);

        // Calculate hours portion (each hour = 0.125 day)
        // Use floor to get whole hours only
        $hoursFromFraction = floor($dayFraction / 0.125);
        $remainingFraction = round($dayFraction - ($hoursFromFraction * 0.125), 3);

        // Find the exact or closest match in MinutesToDayFraction table
        $minutes = 0;
        if ($remainingFraction > 0.0001) { // Use small epsilon to avoid floating point issues
            try {
                // First, try to find an exact match (within 0.001 tolerance)
                $exactMatch = \Illuminate\Support\Facades\DB::table('MinutesToDayFraction')
                    ->select('minutes', 'day_fraction')
                    ->whereRaw('ABS(day_fraction - ?) <= 0.001', [$remainingFraction])
                    ->orderByRaw('ABS(day_fraction - ?)', [$remainingFraction])
                    ->first();

                if ($exactMatch) {
                    $minutes = (int)$exactMatch->minutes;
                } else {
                    // If no exact match, find the closest match
                    $closestAbove = \Illuminate\Support\Facades\DB::table('MinutesToDayFraction')
                        ->select('minutes', 'day_fraction')
                        ->where('day_fraction', '>=', $remainingFraction)
                        ->orderBy('day_fraction', 'asc')
                        ->first();

                    $closestBelow = \Illuminate\Support\Facades\DB::table('MinutesToDayFraction')
                        ->select('minutes', 'day_fraction')
                        ->where('day_fraction', '<=', $remainingFraction)
                        ->orderBy('day_fraction', 'desc')
                        ->first();

                    if ($closestAbove && $closestBelow) {
                        $diffAbove = abs($closestAbove->day_fraction - $remainingFraction);
                        $diffBelow = abs($remainingFraction - $closestBelow->day_fraction);
                        $minutes = $diffAbove <= $diffBelow ? (int)$closestAbove->minutes : (int)$closestBelow->minutes;
                    } elseif ($closestAbove) {
                        $minutes = (int)$closestAbove->minutes;
                    } elseif ($closestBelow) {
                        $minutes = (int)$closestBelow->minutes;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to static lookup when DB table is unavailable
            }

            if ($minutes === 0) {
                $minutes = self::minutesFromRemainingFraction($remainingFraction);
            }
        }

        $totalMinutes = ($hoursFromFraction * 60) + $minutes;

        return [
            'hours' => (int) intdiv($totalMinutes, 60),
            'minutes' => (int) ($totalMinutes % 60),
            'total_minutes' => (int) $totalMinutes
        ];
    }

    /**
     * Convert day fraction to total minutes.
     * This is a convenience wrapper around dayFractionToHoursMinutes.
     * 
     * @param float $dayFraction The day fraction value
     * @return int Total minutes
     */
    public static function dayFractionToMinutes(float $dayFraction): int
    {
        $result = self::dayFractionToHoursMinutes($dayFraction);
        return $result['total_minutes'];
    }

    /**
     * Format day fraction as "X hrs and Y mins" or similar human-readable format.
     * 
     * @param float $dayFraction The day fraction value
     * @return string Human-readable format (e.g., "7 hrs and 51 mins", "8 mins", "1 hr")
     */
    public static function formatDayFractionHuman(float $dayFraction): string
    {
        $result = self::dayFractionToHoursMinutes($dayFraction);
        $hours = $result['hours'];
        $minutes = $result['minutes'];

        $hLabel = $hours === 1 ? 'hr' : 'hrs';
        $mLabel = $minutes === 1 ? 'min' : 'mins';
        
        if ($hours > 0 && $minutes > 0) {
            return "$hours $hLabel and $minutes $mLabel";
        }
        if ($hours > 0) {
            return "$hours $hLabel";
        }
        if ($minutes > 0) {
            return "$minutes $mLabel";
        }
        return "0 mins";
    }

    /**
     * Convert total minutes to day fraction using MinutesToDayFraction lookup table.
     * Uses the same logic as database function fn_MinutesToDayFraction.
     * 
     * @param int|float $totalMinutes Total minutes to convert
     * @return float Day fraction (rounded to 3 decimals)
     */
    public static function minutesToDayFraction($totalMinutes): float
    {
        if (!$totalMinutes || $totalMinutes <= 0) {
            return 0.000;
        }

        $total = round($totalMinutes);
        $hours = intval($total / 60);
        $remainingMinutes = intval($total % 60);

        // Hours fraction: each hour = 0.125 day
        $hoursFraction = $hours * 0.125;

        // Get minutes fraction from lookup table
        $minutesFraction = 0.0;
        try {
            $minuteFraction = \Illuminate\Support\Facades\DB::table('MinutesToDayFraction')
                ->where('minutes', $remainingMinutes)
                ->value('day_fraction');
            $minutesFraction = floatval($minuteFraction ?? 0);
        } catch (\Throwable $e) {
            // Fall through to static lookup
        }

        if ($minutesFraction <= 0 && $remainingMinutes > 0) {
            $minutesFraction = self::minuteFractionFromLookup($remainingMinutes);
        }

        // Total day fraction = hours fraction + minutes fraction
        return round($hoursFraction + $minutesFraction, 3);
    }

    /**
     * Calculate work_hours from late, undertime, and absent when canceling offset.
     * Formula: work_hours = 8 hours - (late + undertime + absent in minutes) converted to day fraction
     * 
     * @param float $late Late day fraction (e.g., 0.046 = 22 minutes)
     * @param float $undertime Undertime day fraction (e.g., 0.035 = 17 minutes)
     * @param float $absent Absent days (e.g., 1.000 = 1 day = 480 minutes)
     * @param float $scheduledWorkHours Scheduled work hours per day (default: 8.0)
     * @return float Work hours as day fraction (rounded to 3 decimals)
     */
    public static function calculateWorkHoursFromOffset(float $late, float $undertime, float $absent, float $scheduledWorkHours = 8.0): float
    {
        // Convert late day fraction to minutes
        $lateMinutes = self::dayFractionToMinutes($late);
        
        // Convert undertime day fraction to minutes
        $undertimeMinutes = self::dayFractionToMinutes($undertime);
        
        // Convert absent days to minutes (1 day = 8 hours = 480 minutes, but use scheduled work hours)
        $scheduledWorkMinutes = round($scheduledWorkHours * 60);
        $absentMinutes = round($absent * $scheduledWorkMinutes);
        
        // Total offset minutes
        $totalOffsetMinutes = $lateMinutes + $undertimeMinutes + $absentMinutes;
        
        // Work hours minutes = scheduled work minutes - total offset minutes
        $workHoursMinutes = max(0, $scheduledWorkMinutes - $totalOffsetMinutes);
        
        // Convert work hours minutes back to day fraction using lookup table
        return self::minutesToDayFraction($workHoursMinutes);
    }
}


