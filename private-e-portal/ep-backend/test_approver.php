<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$empReqCtrl = new \App\Http\Controllers\EmployeeRequestController();

echo "--- James White (id=1) ---\n";
$res1 = $empReqCtrl->checkApproverPipelineAccess(1);
print_r(json_decode($res1->getContent()));

$headers = DB::table('approver_headers')->where('approver_id_1', '>', 0)->first();
if ($headers) {
    echo "\n--- Configured Approver (id={$headers->approver_id_1}) ---\n";
    $res2 = $empReqCtrl->checkApproverPipelineAccess($headers->approver_id_1);
    print_r(json_decode($res2->getContent()));
}
