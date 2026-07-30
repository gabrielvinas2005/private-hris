<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$id = 1;
$header = DB::table('ipcr_headers as a')
    ->leftJoin('months as f', 'a.month_from', '=', 'f.id')
    ->leftJoin('months as g', 'a.month_to', '=', 'g.id')
    ->select(
        'a.*',
        'a.month_from as month_from_id',
        'a.month_to as month_to_id',
        'f.name as month_from',
        'g.name as month_to'
    )
    ->where('a.id', $id)
    ->first();

echo "Header:\n";
echo "  Department: " . $header->department_id . "\n";
echo "  Division: " . $header->division_id . "\n";
echo "  Section: " . $header->section_id . "\n\n";

// Run the same query as the controller
$rows = DB::table('employees as emp')
    ->leftJoin('employee_ipcr as ei', 'ei.employee_id', '=', 'emp.id')
    ->leftJoin('ipcr_details as d', function ($join) use ($id) {
        $join->on('d.employee_ipcr_id', '=', 'ei.id')
            ->where('d.ipcr_header_id', '=', $id);
    })
    ->leftJoin('status as s', 's.id', '=', 'd.status_id')
    ->where('emp.department_id', $header->department_id)
    ->where('emp.is_employee', true)
    ->where('emp.active', true)
    ->when($header->division_id != 0, function ($q) use ($header) {
        $q->where('emp.division_id', $header->division_id);
    })
    ->when($header->section_id != 0, function ($q) use ($header) {
        $q->where('emp.section_id', $header->section_id);
    })
    ->select(
        'emp.id',
        'emp.employee_no',
        'emp.first_name',
        'emp.last_name',
        'ei.id as employee_ipcr_id',
        'd.id as ipcr_detail_id'
    )
    ->get();

echo "Employees found: " . $rows->count() . "\n\n";
foreach ($rows as $row) {
    echo "  - " . $row->employee_no . ": " . $row->first_name . " " . $row->last_name . "\n";
    echo "    Employee IPCR ID: " . ($row->employee_ipcr_id ?: 'NULL') . "\n";
    echo "    IPCR Detail ID: " . ($row->ipcr_detail_id ?: 'NULL') . "\n\n";
}

