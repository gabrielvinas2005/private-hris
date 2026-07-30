<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking both employees ===\n\n";

$employees = DB::table('employees')
    ->whereIn('employee_no', ['B-21-21', 'B-18-05'])
    ->get();

foreach ($employees as $e) {
    echo "Employee: " . $e->employee_no . " - " . $e->first_name . " " . $e->last_name . "\n";
    echo "  Department ID: " . $e->department_id . "\n";
    echo "  Division ID: " . ($e->division_id ?? 'NULL') . "\n";
    echo "  Section ID: " . ($e->section_id ?? 'NULL') . "\n";
    echo "  Active: " . ($e->active ? 'Yes' : 'No') . "\n";
    echo "  Is Employee: " . ($e->is_employee ? 'Yes' : 'No') . "\n\n";
}

