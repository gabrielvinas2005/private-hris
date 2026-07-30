<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking IPCR Header 1 ===\n\n";

$header = DB::table('ipcr_headers')->where('id', 1)->first();
if (!$header) {
    echo "IPCR Header 1 not found\n";
    exit;
}

echo "Department ID: " . $header->department_id . "\n";
echo "Division ID: " . ($header->division_id ?: 'NULL') . "\n";
echo "Section ID: " . ($header->section_id ?: 'NULL') . "\n\n";

echo "=== Employees in this department with IPCR data ===\n\n";
$employeesWithIpcr = DB::table('employees as e')
    ->join('employee_ipcr as ei', 'ei.employee_id', '=', 'e.id')
    ->where('e.department_id', $header->department_id)
    ->where('e.active', true)
    ->select('e.id', 'e.employee_no', 'e.first_name', 'e.last_name', 'ei.id as ipcr_id')
    ->get();

if ($employeesWithIpcr->isEmpty()) {
    echo "No employees with IPCR data in this department\n";
} else {
    foreach ($employeesWithIpcr as $emp) {
        echo "Employee: " . $emp->employee_no . " - " . $emp->first_name . " " . $emp->last_name . "\n";
        echo "  IPCR ID: " . $emp->ipcr_id . "\n";
        
        $outputs = DB::table('employee_ipcr_outputs')->where('employee_ipcr_id', $emp->ipcr_id)->get();
        echo "  Outputs: " . $outputs->count() . "\n";
        
        if ($outputs->count() > 0) {
            $recalibrations = DB::table('ipcr_recalibrations as r')
                ->leftJoin('status as s', 's.id', '=', 'r.status')
                ->whereIn('r.employee_ipcr_output_id', $outputs->pluck('id')->toArray())
                ->select('r.recalibration_level', 'r.status', 's.name as status_name')
                ->get();
            
            echo "  Recalibrations: " . $recalibrations->count() . "\n";
            foreach ($recalibrations as $r) {
                echo "    - Level: " . $r->recalibration_level . 
                     ", Status ID: " . $r->status . 
                     ", Status Name: " . ($r->status_name ?: 'NULL') . "\n";
            }
        }
        echo "\n";
    }
}

