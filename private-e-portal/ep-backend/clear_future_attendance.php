<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$empId = 1; // James White
$fromDate = '2026-08-27';

// Reset time_data punch logs and hours for James White from August 27, 2026 onwards
$affected = DB::table('time_data')
    ->where('employee_id', $empId)
    ->whereDate('date', '>=', $fromDate)
    ->update([
        'am_in' => null,
        'am_out' => null,
        'pm_in' => null,
        'pm_out' => null,
        'break_in' => null,
        'break_out' => null,
        'work_hours' => 0,
        'late' => 0,
        'undertime' => 0,
        'absent' => 0,
        'leave' => 0,
        'is_ob' => 0,
        'is_wfh' => 0,
        'is_ot' => 0,
        'ot_hours' => 0,
        'remarks' => '',
    ]);

echo "Cleared attendance punch logs for James White for date >= {$fromDate}. Affected rows: {$affected}\n";
