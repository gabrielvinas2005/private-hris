<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$empId = 1; // James White
$periodId = 6; // Aug 16-31, 2026

$records = [
    [
        'date' => '2026-08-17',
        'am_in' => '07:55:00',
        'am_out' => '12:00:00',
        'pm_in' => '13:00:00',
        'pm_out' => '17:02:00',
        'work_hours' => 8.0,
        'late' => 0,
        'undertime' => 0,
        'absent' => 0,
        'is_holiday' => 0,
        'remarks' => ''
    ],
    [
        'date' => '2026-08-18',
        'am_in' => '07:50:00',
        'am_out' => '12:00:00',
        'pm_in' => '13:00:00',
        'pm_out' => '17:00:00',
        'work_hours' => 8.0,
        'late' => 0,
        'undertime' => 0,
        'absent' => 0,
        'is_holiday' => 0,
        'remarks' => ''
    ],
    [
        'date' => '2026-08-19',
        'am_in' => '08:05:00',
        'am_out' => '12:00:00',
        'pm_in' => '13:00:00',
        'pm_out' => '17:00:00',
        'work_hours' => 7.9,
        'late' => 5,
        'undertime' => 0,
        'absent' => 0,
        'is_holiday' => 0,
        'remarks' => ''
    ],
    [
        'date' => '2026-08-21',
        'am_in' => '07:48:00',
        'am_out' => '12:00:00',
        'pm_in' => '13:00:00',
        'pm_out' => '17:15:00',
        'work_hours' => 8.25,
        'late' => 0,
        'undertime' => 0,
        'absent' => 0,
        'is_holiday' => 0,
        'remarks' => ''
    ],
    [
        'date' => '2026-08-25',
        'am_in' => '07:58:00',
        'am_out' => '12:00:00',
        'pm_in' => '13:00:00',
        'pm_out' => '17:05:00',
        'work_hours' => 8.0,
        'late' => 0,
        'undertime' => 0,
        'absent' => 0,
        'is_holiday' => 0,
        'remarks' => ''
    ],
];

foreach ($records as $r) {
    $existing = DB::table('time_data')
        ->where('employee_id', $empId)
        ->whereDate('date', $r['date'])
        ->first();

    $data = [
        'employee_id' => $empId,
        'payroll_period_id' => $periodId,
        'date' => $r['date'],
        'am_in' => $r['am_in'],
        'am_out' => $r['am_out'],
        'pm_in' => $r['pm_in'],
        'pm_out' => $r['pm_out'],
        'work_hours' => $r['work_hours'],
        'late' => $r['late'],
        'undertime' => $r['undertime'],
        'absent' => $r['absent'],
        'is_holiday' => $r['is_holiday'],
        'remarks' => $r['remarks'],
    ];

    if ($existing) {
        DB::table('time_data')->where('id', $existing->id)->update($data);
        echo "Updated time_data for {$r['date']}\n";
    } else {
        DB::table('time_data')->insert($data);
        echo "Inserted time_data for {$r['date']}\n";
    }
}

// Reset is_holiday to 0 for all regular days in period 6
DB::table('time_data')
    ->where('employee_id', $empId)
    ->where('payroll_period_id', $periodId)
    ->update(['is_holiday' => 0]);

echo "Successfully seeded James White attendance logs!\n";
