<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$emp = DB::table('employees')->where('first_name', 'like', '%James%')->first();
if (!$emp) {
    echo "No James employee found\n";
    exit;
}

echo "Employee: ID {$emp->id}, Name: {$emp->first_name} {$emp->last_name}\n";

$requests = DB::table('time_data_request')->where('employee_id', $emp->id)->get();
echo "\n--- TIME DATA REQUESTS ---\n";
foreach ($requests as $r) {
    echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
}

$todayLogs = DB::table('time_data')->where('employee_id', $emp->id)->where('date', '2026-08-24')->get();
echo "\n--- TIME DATA FOR TODAY (2026-08-24) ---\n";
foreach ($todayLogs as $l) {
    echo json_encode($l, JSON_PRETTY_PRINT) . "\n";
}

$recentLogs = DB::table('time_data')->where('employee_id', $emp->id)->orderBy('date', 'desc')->take(5)->get();
echo "\n--- RECENT TIME DATA LOGS ---\n";
foreach ($recentLogs as $l) {
    echo "ID: {$l->id}, Date: {$l->date}, AM IN: {$l->am_in}, AM OUT: {$l->am_out}, PM IN: {$l->pm_in}, PM OUT: {$l->pm_out}, Remarks: {$l->remarks}, For Approval: {$l->for_approval}\n";
}
