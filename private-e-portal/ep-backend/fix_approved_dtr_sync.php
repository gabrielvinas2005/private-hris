<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING APPROVED DTR SYNC ===\n";

// Find all approved time_data_request records
$approvedRequests = DB::table('time_data_request')
    ->where(function($q) {
        $q->where('status', 1)
          ->orWhere('approved_1', 1)
          ->orWhere('approved_2', 1);
    })
    ->get();

foreach ($approvedRequests as $req) {
    echo "Processing Request ID #{$req->id} for Employee ID #{$req->employee_id}...\n";

    // 1. Update any time_data records linked directly by dtr_request_id
    $count1 = DB::table('time_data')
        ->where('dtr_request_id', $req->id)
        ->update(['for_approval' => 1, 'is_edited' => 0]);
    echo "  Updated {$count1} time_data rows linked by dtr_request_id\n";

    // 2. Update time_data for employee in payroll_period if applicable
    if (!empty($req->payroll_period_id)) {
        $count2 = DB::table('time_data')
            ->where('employee_id', $req->employee_id)
            ->where('payroll_period_id', $req->payroll_period_id)
            ->update(['for_approval' => 1, 'is_edited' => 0]);
        echo "  Updated {$count2} time_data rows in payroll_period {$req->payroll_period_id}\n";
    }
    
    // 3. Update time_data rows with is_edited = 1 for this employee
    $count3 = DB::table('time_data')
        ->where('employee_id', $req->employee_id)
        ->where('is_edited', 1)
        ->update(['for_approval' => 1, 'is_edited' => 0]);
    echo "  Updated {$count3} time_data rows with is_edited = 1\n";
}

echo "=== COMPLETED ===\n";
