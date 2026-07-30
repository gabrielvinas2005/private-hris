<?php

require __DIR__ . '/vendor/autoload.php';

use App\Helpers\Time_Calculation as TC;

function line($label, $value) {
    echo str_pad($label . ':', 28) . $value . PHP_EOL;
}

// Example: Regular 8AM-5PM with 1-hour break (12:00-13:00)
$scheduledAmIn = '08:00:00';
$scheduledBreakIn = '12:00:00';
$scheduledBreakOut = '13:00:00';
$scheduledPmOut = '17:00:00';

$actualAmIn = '08:24:00';
$actualPmOut = '17:10:00';

$scheduledMin = TC::computeScheduledWorkMinutes($scheduledAmIn, $scheduledBreakIn, $scheduledBreakOut, $scheduledPmOut); // 480
$lateMin = TC::computeLateMinutes($actualAmIn, $scheduledAmIn, 0, false); // 24
$undertimeMin = TC::computeUndertimeMinutes($actualPmOut, $scheduledPmOut, false); // 0
$workedMin = TC::computeWorkingMinutes($scheduledMin, $lateMin, $undertimeMin); // 456
$workedDec = TC::minutesToHourMinuteDecimal($workedMin); // 7.36
$workedHuman = TC::formatHuman($workedMin); // 7 hrs and 36 mins

echo "Time Calculation Demo" . PHP_EOL;
echo str_repeat('=', 50) . PHP_EOL;
line('Scheduled minutes', $scheduledMin);
line('Late minutes', $lateMin);
line('Undertime minutes', $undertimeMin);
line('Worked minutes', $workedMin);
line('Worked (decimal HH.MM)', number_format($workedDec, 2));
line('Worked (human)', $workedHuman);
echo str_repeat('-', 50) . PHP_EOL;

// Round-trip check: convert HH.MM back to minutes
$roundTripMin = TC::hourMinuteDecimalToMinutes($workedDec);
line('Round-trip minutes', $roundTripMin);





