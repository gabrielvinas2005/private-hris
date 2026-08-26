<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$empId = 5; // Barbara Adams
$DTR_TYPE_ID = 11;

echo "=== APPROVER HEADERS FOR DTR (type 11) ===\n";
$headers = DB::table('approver_headers')->where('type_id', $DTR_TYPE_ID)->get();
foreach ($headers as $h) {
    echo "Header ID: {$h->id}, Dept: {$h->department_id}, Appr1: {$h->approver_id_1}, Appr2: {$h->approver_id_2}, Appr3: {$h->approver_id_3}\n";
    $details = DB::table('approver_details')->where('approver_id', $h->id)->get();
    echo "  Details count: " . $details->count() . "\n";
    foreach ($details as $d) {
        echo "    Detail Employee ID: {$d->employee_id}\n";
    }
}

echo "\n=== JAMES WHITE TIME DATA REQUEST ===\n";
$requests = DB::table('time_data_request')->where('employee_id', 1)->get();
foreach ($requests as $r) {
    echo "Request ID: {$r->id}, EmpID: {$r->employee_id}, Status: {$r->status}, Approved1: {$r->approved_1}, Approved2: {$r->approved_2}\n";
}

echo "\n=== TESTING DailyTimeRecordController->loadDTRRequest(10087) ===\n";
$controller = app()->make(App\Http\Controllers\DailyTimeRecordController::class);
$res = $controller->loadDTRRequest(10087);
echo json_encode($res->getData(), JSON_PRETTY_PRINT) . "\n";
