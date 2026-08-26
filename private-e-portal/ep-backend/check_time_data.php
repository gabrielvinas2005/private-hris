<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$jamesId = 1;

echo "=== TIME_DATA RECORDS FOR JAMES WHITE (employee_id=1) ===\n";
$rows = DB::table('time_data')
    ->where('employee_id', $jamesId)
    ->orderBy('date', 'asc')
    ->get();

foreach ($rows as $r) {
    echo "ID: {$r->id} | Date: {$r->date} | AM In: {$r->am_in} | AM Out: {$r->am_out} | PM In: {$r->pm_in} | PM Out: {$r->pm_out} | WorkHrs: {$r->work_hours} | is_holiday: {$r->is_holiday} | holiday_id: {$r->holiday_id} | remarks: {$r->remarks}\n";
}
