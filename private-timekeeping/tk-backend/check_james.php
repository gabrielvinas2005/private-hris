<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$employees = DB::table('employees')
    ->where(function($q) {
        $q->where('first_name', 'like', '%James%')
          ->orWhere('last_name', 'like', '%White%');
    })->get();

echo "=== EMPLOYEES MATCHING JAMES WHITE ===\n";
foreach ($employees as $e) {
    echo "ID: {$e->id} | Name: {$e->first_name} {$e->last_name} | Salary: {$e->salary} | EmpTypeID: {$e->employment_type_id} | Active: {$e->active}\n";
}

if ($employees->isNotEmpty()) {
    foreach ($employees as $emp) {
        echo "\n=== TIME DATA FOR EMP {$emp->id} ({$emp->first_name} {$emp->last_name}) ===\n";
        $tds = DB::table('time_data')
            ->leftJoin('holidays', 'holidays.id', '=', 'time_data.holiday_id')
            ->leftJoin('holiday_types', 'holiday_types.id', '=', 'holidays.holiday_type')
            ->select('time_data.*', 'holidays.name as holiday_name', 'holiday_types.rate as holiday_rate', 'holiday_types.absent_with_pay')
            ->where('time_data.employee_id', $emp->id)
            ->orderBy('time_data.date', 'desc')
            ->take(15)
            ->get();
        foreach ($tds as $td) {
            echo "Date: {$td->date} | AM In: {$td->am_in} | AM Out: {$td->am_out} | PM In: {$td->pm_in} | PM Out: {$td->pm_out} | Hrs: {$td->work_hours} | IsHoliday: {$td->is_holiday} | HolID: {$td->holiday_id} ({$td->holiday_name}) | HolRate: {$td->holiday_rate} | AbsentWithPay: {$td->absent_with_pay} | HolPay: {$td->holiday_pay} | OTPay: {$td->ot_pay}\n";
        }

        echo "\n=== DTR CORRECTIONS / DISPUTES FOR EMP {$emp->id} ===\n";
        $corrs = DB::table('dtr_corrections')
            ->where('employee_id', $emp->id)
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($corrs as $c) {
            echo "ID: {$c->id} | Date: {$c->target_date} | AM In: {$c->am_in} | PM Out: {$c->pm_out} | Status: " . (isset($c->status) ? $c->status : (isset($c->approved) ? $c->approved : 'N/A')) . " | Created: {$c->created_at}\n";
        }

        echo "\n=== OVERTIME APPLICATIONS FOR EMP {$emp->id} ===\n";
        $ots = DB::table('overtime_applications')
            ->where('employee_id', $emp->id)
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($ots as $ot) {
            echo "ID: {$ot->id} | Date: {$ot->overtime_date} | Hours: {$ot->overtime_hours} | OTTypeID: {$ot->overtime_type_id} | Status: " . (isset($ot->status) ? $ot->status : 'N/A') . "\n";
        }
    }
}
