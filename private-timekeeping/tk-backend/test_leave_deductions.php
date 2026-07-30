<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing leave deductions for Employee 338...\n";

try {
    $controller = new \App\Http\Controllers\LeaveCreditCardController();
    
    echo "=== Testing Employee 338 for 2025 (with leave deductions) ===\n";
    $result2025 = $controller->getLeaveCredits(338, 2025);
    $data2025 = $result2025->getData();
    
    echo "Response structure:\n";
    print_r($data2025);
    
    if (isset($data2025->data->beginning_balances)) {
        $balances = $data2025->data->beginning_balances;
        
        echo "\n2025 Results with Leave Deductions:\n";
        echo "August 2025: VL=" . $balances[7]->vl_balance . " (should be reduced by 3 days leave)\n";
        echo "August 2025: VL With Pay=" . $balances[7]->vl_with_pay . "\n";
        echo "August 2025: VL Without Pay=" . $balances[7]->vl_without_pay . "\n";
        echo "September 2025: VL=" . $balances[8]->vl_balance . "\n";
        echo "September 2025: VL With Pay=" . $balances[8]->vl_with_pay . "\n";
        echo "September 2025: VL Without Pay=" . $balances[8]->vl_without_pay . "\n";
        
        echo "\nExpected Results:\n";
        echo "August 2025: Should show 3 days of leave (2025-08-27 to 2025-08-29)\n";
        echo "September 2025: Should show 2 days of leave (2025-09-03 to 2025-09-05)\n";
    } else {
        echo "No beginning balances found for 2025\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
