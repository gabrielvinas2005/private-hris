<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record (DTR)</title>
    <style>
        @page {
            size: A4 portrait;
            margin-top: 1in;
            margin-bottom: 1in;
            margin-left: 0;
            margin-right: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Global A4 Page Wrapper */
        .dtr-page-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Inner content wrapper - applies 2 inch margins to content inside wrappers */
        .content-inner {
            margin-left: 2in;
            margin-right: 2in;
            padding: 10px 0;
        }

        /* Period details content - applies 1 inch margins */
        .period-content-inner {
            margin-left: 1in;
            margin-right: 1in;
            padding: 10px 0;
        }

        /* Table content wrapper - adjustable margins */
        .table-content-inner {
            margin-left: 1.5in;
            margin-right: 1.5in;
            padding: 10px 0;
        }

        /* Certification content wrapper - adjustable margins */
        .certification-content-inner {
            margin-left: 2in;
            margin-right: 2in;
            padding: 10px 0;
            text-align: center;
        }

        /* 1. Civil Service Form Header - Positioned at upper left */
        .civil-form-header {
            position: absolute;
            top: 0;
            left: 0;
            text-align: left;
            font-size: 9px;
            margin: 0;
            padding: 5px;
            z-index: 10;
        }

        /* 2. Title Header Wrapper - Contains title, divider, and employee name */
        .title-header-wrapper {
            width: 100%;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        /* 3. Period Details Wrapper - Contains period and official hours */
        .period-details-wrapper {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10px;
        }

        /* 4. Table Wrapper - Contains the DTR table */
        .table-wrapper {
            width: 100%;
            margin: 0;
        }

        /* 5. Certification Wrapper - Contains certification and signature */
        .certification-wrapper {
            width: 100%;
            margin-top: 20px;
        }

        /* Title Header Section Styles */
        .title-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .title-divider {
            font-size: 10px;
            margin-bottom: 8px;
        }

        .employee-name-section {
            margin-bottom: 8px;
            text-align: center;
        }

        .employee-name {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .employee-name-label-italic {
            font-size: 10px;
            font-style: italic;
            text-align: center;
            margin-top: 2px;
        }

        /* Period Details Section Styles */
        .period-section {
            margin-bottom: 8px;
            font-size: 10px;
        }

        .period-month-line {
            font-size: 14px;
            font-style: italic;
        }

        .period-month-year {
            margin-left: 70px;
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
        }

        .official-hours {
            font-size: 9px;
            margin-top: 5px;
        }

        .official-hours-main-row {
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .official-hours-main-label {
            display: inline-block;
            font-style: italic;
            vertical-align: top;
        }

        .hours-right-stack {
            display: inline-block;
            margin-left: 18px;
            vertical-align: top;
        }

        .hours-inline-row {
            display: block;
            white-space: nowrap;
        }

        .hours-inline-row + .hours-inline-row {
            margin-top: 3px;
        }

        .hours-inline-row .official-hours-label {
            display: inline-block;
            width: 80px;
        }

        .hours-inline-row .official-hours-value {
            display: inline-block;
            flex: none;
            min-width: 120px;
        }

        .official-hours-row {
            display: flex;
            margin-bottom: 2px;
        }

        .official-hours-label {
            width: 80px;
            font-style: italic;
        }

        .official-hours-value {
            border-bottom: 1px solid #000;
            flex: 1;
            margin-left: 5px;
            min-height: 12px;
        }

        /* ============================================
           TABLE CELL STYLING - ADJUST CELL BOXES HERE
           ============================================ */

        .dtr-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 8px;
            table-layout: auto;
        }

        /* ALL CELLS - General styling for all table cells (th and td) */
        .dtr-table th,
        .dtr-table td {
            border: 1px solid #000;        /* Cell border: color, width, style */
            padding: 2px;                   /* Cell padding: space inside cells (top right bottom left) */
            text-align: center;             /* Text alignment: center, left, right, justify */
            vertical-align: middle;         /* Vertical alignment: top, middle, bottom */
        }

        /* HEADER CELLS - Styling for table header cells only */
        .dtr-table th {
            font-weight: bold;
            background-color: #f0f0f0;     /* Header background color */
            font-size: 7px;                 /* Header font size */
        }

        /* DAY COLUMN - Day of month column (e.g., 23, 7) */
        .day-col {
            width: 25px;                    /* Column width */
            /* Add padding, font-size, etc. here if needed */
        }

        /* TIME COLUMNS - AM/PM Arrival/Departure columns */
        .time-col {
            width: 45px;                    /* Column width */
            font-size: 7px;                 /* Font size for time cells */
            /* Add padding, text-align, etc. here if needed */
        }

        /* LATE HOURS COLUMN - Hours portion of late */
        .late-hours-col {
            width: 35px;                    /* Column width */
            /* Add padding, font-size, etc. here if needed */
        }

        /* LATE MINUTES COLUMN - Minutes portion of late */
        .late-mins-col {
            width: 35px;                    /* Column width */
            /* Add padding, font-size, etc. here if needed */
        }

        /* UNDERTIME HOURS COLUMN - Hours portion of undertime */
        .undertime-hours-col {
            width: 35px;                    /* Column width */
            /* Add padding, font-size, etc. here if needed */
        }

        /* UNDERTIME MINUTES COLUMN - Minutes portion of undertime */
        .undertime-mins-col {
            width: 35px;                    /* Column width */
            /* Add padding, font-size, etc. here if needed */
        }

        /* REMARKS COLUMN - Remarks/notes column */
        .remarks-col {
            width: 50px;                   /* Column width */
            text-align: left;               /* Text alignment (overrides center from general cells) */
            padding: 2px;               /* Padding (top/bottom left/right) */
            font-size: 7px;                 /* Font size */
        }

        /* HIDE LATE COLUMN */
        th.late-header,
        th.late-hours-col,
        th.late-mins-col,
        td.late-hours-col,
        td.late-mins-col {
            display: none;
        }

        .weekend-text {
            color: #d32f2f;
            font-weight: bold;
        }

        .certification {
            margin-top: 15px;
            font-size: 9px;
            text-align: center;
            line-height: 1.4;
        }

        .verification-statement {
            margin-top: 20px;
            font-size: 9px;
            text-align: center;
        }

        .signature-section {
            margin-top: 20px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 5px auto 5px auto;
            width: 40%;
            display: block;
        }

        .signature-label {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }

        .signature-label-italic {
            font-size: 9px;
            font-style: italic;
            text-align: center;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    @php
        // Calculate dates for period display (used in multiple sections)
                $startDate = isset($payroll_period) && isset($payroll_period['attendance_start_date']) && $payroll_period['attendance_start_date']
                    ? \Carbon\Carbon::parse($payroll_period['attendance_start_date'])->startOfDay()
                    : \Carbon\Carbon::now()->startOfDay();
                $endDate = isset($payroll_period) && isset($payroll_period['attendance_end_date']) && $payroll_period['attendance_end_date']
                    ? \Carbon\Carbon::parse($payroll_period['attendance_end_date'])->startOfDay()
                    : $startDate->copy()->endOfMonth();

                // DTR period line split into label + month/year value
                if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
                    $dtrPeriodLabel = 'For the month of';
                    $dtrPeriodMonthYear = $startDate->format('F Y');
                } elseif ($startDate->format('Y') === $endDate->format('Y')) {
                    $dtrPeriodLabel = 'For the months of';
                    $dtrPeriodMonthYear = $startDate->format('F') . ' - ' . $endDate->format('F Y');
                } else {
                    $dtrPeriodLabel = 'For the months of';
                    $dtrPeriodMonthYear = $startDate->format('F Y') . ' - ' . $endDate->format('F Y');
                }

                // Determine which month spans (for Month & Year display on right)
                $startMonth = $startDate->format('F');
                $endMonth = $endDate->format('F');
                $startYear = $startDate->format('Y');
                $endYear = $endDate->format('Y');

                $monthDisplay = ($startMonth === $endMonth && $startYear === $endYear)
                    ? $startMonth
                    : $startMonth . ' - ' . $endMonth;
                $yearDisplay = ($startYear === $endYear)
                    ? $startYear
                    : $startYear . ' - ' . $endYear;
            @endphp

    <!-- Global A4 Page Wrapper -->
    <div class="dtr-page-wrapper">
        <!-- 1. Form Header - Positioned at upper left edge -->
        <div class="civil-form-header">
            <p>EMPLOYEE DAILY TIME RECORD</p>
        </div>

        <!-- 2. Title Header Wrapper - Contains title, divider, and employee name -->
        <div class="title-header-wrapper">
            <div class="content-inner">
                <div class="title-header">
                    <div class="title">DAILY TIME RECORD</div>
                    <div class="title-divider">---o0o---</div>
                </div>

                <div class="employee-name-section">
                    <div class="employee-name">
                        @php
                            $fn = trim((string) data_get($employee, 'first_name', ''));
                            $mn = trim((string) data_get($employee, 'middle_name', ''));
                            $ln = trim((string) data_get($employee, 'last_name', ''));
                            if ($fn !== '' && $ln !== '') {
                                $dtrEmployeeDisplayName = $mn !== ''
                                    ? $fn . ' ' . mb_substr($mn, 0, 1) . '. ' . $ln
                                    : $fn . ' ' . $ln;
                            } else {
                                $dtrEmployeeDisplayName = trim((string) data_get($employee, 'name', ''));
                            }
                        @endphp
                        @if($dtrEmployeeDisplayName !== '')
                            {{ strtoupper($dtrEmployeeDisplayName) }}
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="employee-name-label-italic">(Name)</div>
                </div>
            </div>
        </div>

        <!-- 3. Period Details Wrapper - Contains period and official hours -->
        <div class="period-details-wrapper">
            <div class="period-content-inner">
                <div class="period-section">
                    <div class="period-month-line">
                        <span>{{ $dtrPeriodLabel }}</span>
                        <span class="period-month-year">{{ $dtrPeriodMonthYear }}</span>
                    </div>
                    <div style="margin-top: 10px;">
                        <div class="official-hours">
                            <div class="official-hours-main-row">
                                <span class="official-hours-main-label">Official hours for arrival and departure</span>
                                <div class="hours-right-stack">
                                    <div class="hours-inline-row">
                                        <span class="official-hours-label">Regular days:</span>
                                        <span class="official-hours-value">{{ $regular_hours }}</span>
                                    </div>
                                    <div class="hours-inline-row">
                                        <span class="official-hours-label">Saturdays:</span>
                                        <span class="official-hours-value">{{ $saturday_hours }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Table Wrapper - Contains the DTR table -->
        <div class="table-wrapper">
            <div class="table-content-inner">
    <table class="dtr-table">
        <thead>
            <tr>
                <th class="day-col" rowspan="2">Day</th>
                <th class="time-col" colspan="2">A.M.</th>
                <th class="time-col" colspan="2">P.M.</th>
                <th class="late-header" colspan="2">Late</th>
                <th colspan="2">Undertime</th>
                <th class="remarks-col" rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th class="time-col">Arrival</th>
                <th class="time-col">Departure</th>
                <th class="time-col">Arrival</th>
                <th class="time-col">Departure</th>
                <th class="late-hours-col">Hr</th>
                <th class="late-mins-col">Min</th>
                <th class="undertime-hours-col">Hr</th>
                <th class="undertime-mins-col">Min</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Gross day fraction = net + offset (works whether or not applied_offset flag is set)
                $getDisplayDayFraction = function ($rec, string $netField, string $offsetField): float {
                    if (is_array($rec)) {
                        $net = floatval($rec[$netField] ?? 0);
                        $offset = floatval($rec[$offsetField] ?? 0);
                    } else {
                        $net = floatval($rec->{$netField} ?? 0);
                        $offset = floatval($rec->{$offsetField} ?? 0);
                    }

                    return round($net + $offset, 3);
                };

                $fractionToHrMin = function (float $fraction): array {
                    if ($fraction <= 0.0001) {
                        return ['hours' => 0, 'minutes' => 0, 'total_minutes' => 0];
                    }

                    $totalMins = \App\Helpers\Time_Calculation::dayFractionToMinutes($fraction);

                    return [
                        'hours' => intdiv($totalMins, 60),
                        'minutes' => $totalMins % 60,
                        'total_minutes' => $totalMins,
                    ];
                };

                $scheduleTimesMap = $scheduleTimesMap ?? [];

                // Late columns are hidden on this form; visible Undertime Hr/Min show combined tardiness.
                $getTardinessMinutes = function ($rec, string $dateKey = '') use ($getDisplayDayFraction, $fractionToHrMin, &$scheduleTimesMap): int {
                    if (!$rec) {
                        return 0;
                    }

                    $recordIsArray = is_array($rec);
                    $rawAmIn = $recordIsArray ? ($rec['am_in'] ?? null) : ($rec->am_in ?? null);
                    $rawPmOut = $recordIsArray ? ($rec['pm_out'] ?? null) : ($rec->pm_out ?? null);

                    if ($dateKey !== '' && isset($scheduleTimesMap[$dateKey])
                        && !empty($rawAmIn) && !empty($rawPmOut)) {
                        $sched = $scheduleTimesMap[$dateKey];
                        if (!empty($sched['am_in_raw']) && !empty($sched['pm_out_raw'])) {
                            $flexiHours = floatval($sched['flexi_hours'] ?? 0);
                            if ($flexiHours > 0) {
                                $computed = \App\Helpers\Time_Calculation::computeFlexiTardinessMinutes(
                                    $rawAmIn,
                                    $rawPmOut,
                                    $sched['am_in_raw'],
                                    $sched['pm_out_raw'],
                                    $flexiHours,
                                    floatval($sched['grace_period'] ?? 0),
                                    floatval($sched['work_hours'] ?? 8),
                                    (int) ($sched['lunch_break_minutes'] ?? 60)
                                );

                                return $computed['total_minutes'];
                            }
                        }
                    }

                    $lateFraction = $getDisplayDayFraction($rec, 'late', 'late_offset');
                    $undertimeFraction = $getDisplayDayFraction($rec, 'undertime', 'undertime_offset');
                    $totalMins = $fractionToHrMin($lateFraction)['total_minutes']
                        + $fractionToHrMin($undertimeFraction)['total_minutes'];

                    if ($totalMins > 0 || $dateKey === '' || !isset($scheduleTimesMap[$dateKey])) {
                        return $totalMins;
                    }

                    $sched = $scheduleTimesMap[$dateKey];
                    if (empty($rawAmIn) || empty($rawPmOut)
                        || empty($sched['am_in_raw']) || empty($sched['pm_out_raw'])) {
                        return $totalMins;
                    }

                    $computed = \App\Helpers\Time_Calculation::computeTardinessFromScheduleTimes(
                        $rawAmIn,
                        $rawPmOut,
                        $sched['am_in_raw'],
                        $sched['pm_out_raw'],
                        floatval($sched['flexi_hours'] ?? 0),
                        floatval($sched['grace_period'] ?? 0),
                        floatval($sched['work_hours'] ?? 8),
                        (int) ($sched['lunch_break_minutes'] ?? 60)
                    );

                    return $computed['total_minutes'];
                };

                // Sum minutes per day so totals match daily Hr/Min columns
                $totalLateMinutesSum = 0;
                $totalVisibleTardinessMinutesSum = 0;
                if (isset($timeDataMap)) {
                    foreach ($timeDataMap as $recDateKey => $rec) {
                        $recLateValue = $getDisplayDayFraction($rec, 'late', 'late_offset');
                        if ($recLateValue > 0.0001) {
                            $totalLateMinutesSum += \App\Helpers\Time_Calculation::dayFractionToMinutes($recLateValue);
                        }

                        $dateKeyForTotal = is_string($recDateKey) ? $recDateKey : null;
                        if (!$dateKeyForTotal && is_array($rec) && !empty($rec['date'])) {
                            $dateKeyForTotal = \Carbon\Carbon::parse($rec['date'])->format('Y-m-d');
                        } elseif (!$dateKeyForTotal && is_object($rec) && !empty($rec->date)) {
                            $dateKeyForTotal = \Carbon\Carbon::parse($rec->date)->format('Y-m-d');
                        }

                        $totalVisibleTardinessMinutesSum += $getTardinessMinutes($rec, $dateKeyForTotal ?? '');
                    }
                }

                $totalLateHours = intdiv($totalLateMinutesSum, 60);
                $totalLateMinutes = $totalLateMinutesSum % 60;
                $totalUndertimeHours = intdiv($totalVisibleTardinessMinutesSum, 60);
                $totalUndertimeMinutes = $totalVisibleTardinessMinutesSum % 60;

                // Full calendar month(s) for table rows (not payroll attendance date range)
                $datesInPeriod = [];
                $tableStart = $startDate->copy()->startOfMonth();
                $tableEnd = $endDate->copy()->endOfMonth();
                $currentDate = $tableStart->copy();
                while ($currentDate->lte($tableEnd)) {
                    $datesInPeriod[] = $currentDate->copy();
                    $currentDate->addDay();
                }

                $formatTimeValue = function ($value) {
                    if (empty($value)) {
                        return '';
                    }
                    try {
                        return \Carbon\Carbon::parse($value)->format('h:i A');
                    } catch (\Exception $e) {
                        return '';
                    }
                };

            @endphp
            @foreach($datesInPeriod as $dateForRow)
                @php
                    $dateKey = $dateForRow->format('Y-m-d');
                    $record = isset($timeDataMap[$dateKey]) ? $timeDataMap[$dateKey] : null;
                    $dayOfWeek = $dateForRow->dayOfWeek; // 0 = Sunday, 6 = Saturday
                    $isWeekend = $dayOfWeek == 0 || $dayOfWeek == 6;

                    // Format day display: show day number only (e.g., 23, 7)
                    $dayDisplay = $dateForRow->format('j');

                    // Format times
                    $recordIsArray = is_array($record);
                    $amIn = $record ? $formatTimeValue($recordIsArray ? ($record['am_in'] ?? null) : ($record->am_in ?? null)) : '';
                    $amOut = $record ? $formatTimeValue($recordIsArray ? ($record['am_out'] ?? null) : ($record->am_out ?? null)) : '';
                    $pmIn = $record ? $formatTimeValue($recordIsArray ? ($record['pm_in'] ?? null) : ($record->pm_in ?? null)) : '';
                    $pmOut = $record ? $formatTimeValue($recordIsArray ? ($record['pm_out'] ?? null) : ($record->pm_out ?? null)) : '';

                    $isAdjusted = $record && intval($recordIsArray ? ($record['is_adjusted'] ?? 0) : ($record->is_adjusted ?? 0)) === 1;
                    $isRestdayRecord = $record && intval($recordIsArray ? ($record['is_restday'] ?? 0) : ($record->is_restday ?? 0)) === 1;
                    $absentValue = $record ? floatval($recordIsArray ? ($record['absent'] ?? 0) : ($record->absent ?? 0)) : 0;
                    $leaveValue = $record ? floatval($recordIsArray ? ($record['leave'] ?? 0) : ($record->leave ?? 0)) : 0;
                    $isObRecord = $record && intval($recordIsArray ? ($record['is_ob'] ?? 0) : ($record->is_ob ?? 0)) === 1;
                    $isHolidayRecord = $record && intval($recordIsArray ? ($record['is_holiday'] ?? 0) : ($record->is_holiday ?? 0)) === 1;
                    $eligibleForScheduleDefault = $isAdjusted
                        && ! $isRestdayRecord
                        && $absentValue <= 0
                        && $leaveValue <= 0
                        && ! $isObRecord
                        && ! $isHolidayRecord;

                    if ($eligibleForScheduleDefault && isset($scheduleTimesMap[$dateKey])) {
                        $sched = $scheduleTimesMap[$dateKey];
                        if ($amIn === '' && ($sched['am_in'] ?? '') !== '') {
                            $amIn = $sched['am_in'];
                        }
                        if ($pmOut === '' && ($sched['pm_out'] ?? '') !== '') {
                            $pmOut = $sched['pm_out'];
                        }
                    }

                    // Calculate late hours and minutes for this day
                    $lateHours = 0;
                    $lateMinutes = 0;
                    // Calculate undertime hours and minutes for this day
                    $undertimeHours = 0;
                    $undertimeMinutes = 0;
                    $remarks = '';

                    if ($record) {
                        $lateFraction = $getDisplayDayFraction($record, 'late', 'late_offset');

                        $lateHrMin = $fractionToHrMin($lateFraction);
                        $lateHours = $lateHrMin['hours'];
                        $lateMinutes = $lateHrMin['minutes'];

                        $visibleTardinessMins = $getTardinessMinutes($record, $dateKey);
                        $undertimeHours = intdiv($visibleTardinessMins, 60);
                        $undertimeMinutes = $visibleTardinessMins % 60;

                        // Get remarks - handle both array and object access
                        $remarks = trim(is_array($record) ? ($record['remarks'] ?? '') : ($record->remarks ?? ''));
                    }

                    // Leave remarks blank for rest days/weekends unless explicitly set
                @endphp
                <tr>
                    <td class="day-col">{{ $dayDisplay }}</td>
                    <td class="time-col">{{ $amIn }}</td>
                    <td class="time-col">{{ $amOut }}</td>
                    <td class="time-col">{{ $pmIn }}</td>
                    <td class="time-col">{{ $pmOut }}</td>
                    <td class="late-hours-col">
                        @if($lateHours > 0)
                            {{ $lateHours }}
                        @endif
                    </td>
                    <td class="late-mins-col">
                        @if($lateMinutes > 0)
                            {{ $lateMinutes }}
                        @endif
                    </td>
                    <td class="undertime-hours-col">
                        @if($undertimeHours > 0)
                            {{ $undertimeHours }}
                        @endif
                    </td>
                    <td class="undertime-mins-col">
                        @if($undertimeMinutes > 0)
                            {{ $undertimeMinutes }}
                        @endif
                    </td>
                    <td class="remarks-col">{{ $remarks }}</td>
                </tr>
            @endforeach

            <!-- Total Row -->
            <tr style="font-weight: bold;">
                <td class="day-col">Total</td>
                <td class="time-col"></td>
                <td class="time-col"></td>
                <td class="time-col"></td>
                <td class="time-col"></td>
                <td class="late-hours-col">
                    @if($totalLateHours > 0)
                        {{ $totalLateHours }}
                    @endif
                </td>
                <td class="late-mins-col">
                    @if($totalLateMinutes > 0)
                        {{ $totalLateMinutes }}
                    @endif
                </td>
                <td class="undertime-hours-col">
                    @if($totalUndertimeHours > 0)
                        {{ $totalUndertimeHours }}
                    @endif
                </td>
                <td class="undertime-mins-col">
                    @if($totalUndertimeMinutes > 0)
                        {{ $totalUndertimeMinutes }}
                    @endif
                </td>
                <td class="remarks-col"></td>
            </tr>
        </tbody>
    </table>
            </div>
        </div>

        <!-- 5. Certification Wrapper - Contains certification and signature -->
        <div class="certification-wrapper">
            <div class="certification-content-inner">
            <!-- Certification -->
            <div class="certification">
                <p>I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.</p>
                <br><br><br>
                <!-- <div class="signature-line"></div>
                <div class="signature-label">{{ isset($employee) && isset($employee['name']) ? strtoupper($employee['name']) : 'N/A' }}</div> -->
            </div>

            <!-- Verification Statement -->
            <div class="verification-statement">
                <p>VERIFIED as to the prescribed office hours:</p>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                @php
                    // In Charge (approver_1): from departments.employee_id -> employees.id
                    $approver1 = isset($approvers['approver_1']) && $approvers['approver_1'] ? $approvers['approver_1'] : null;
                    // departments.name: prefer approver payload, else employee's department (still set when dept has no employee_id)
                    $deptNameForSignature = '';
                    if ($approver1 && ! empty(trim((string) ($approver1['department_name'] ?? '')))) {
                        $deptNameForSignature = trim($approver1['department_name']);
                    } elseif (isset($department_name) && $department_name !== null && $department_name !== '') {
                        $deptNameForSignature = trim((string) $department_name);
                    }
                @endphp

                @if($approver1)
                    <!-- In Charge - Department Head -->
                    <div class="signature-label">{{ strtoupper($approver1['name']) }}</div>
                    <div class="signature-line"></div>
                @else
                    <!-- No department head when departments.employee_id is missing -->
                    <div class="signature-label" style="min-height: 0px;">&nbsp;</div>
                    <div class="signature-line"></div>
                @endif
                <!-- @if($deptNameForSignature !== '')
                    <div class="signature-label-italic">{{ $deptNameForSignature }}</div>
                @else
                    <div class="signature-label-italic" style="min-height: 15px;">&nbsp;</div>
                @endif -->
                <div class="signature-label-italic">In Charge</div>
            </div>
        </div>
    </div>
</body>
</html>

