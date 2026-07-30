<?php

namespace App\Helpers;

use Carbon\Carbon;

class Time_Calculation
{
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
                // Get the closest day_fraction value (could be above or below)
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

                // Choose the closest match
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
        }

        $totalMinutes = ($hoursFromFraction * 60) + $minutes;

        return [
            'hours' => (int)$hoursFromFraction,
            'minutes' => (int)$minutes,
            'total_minutes' => (int)$totalMinutes
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
        $minuteFraction = \Illuminate\Support\Facades\DB::table('MinutesToDayFraction')
            ->where('minutes', $remainingMinutes)
            ->value('day_fraction');

        $minutesFraction = floatval($minuteFraction ?? 0);

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


