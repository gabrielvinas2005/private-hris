<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $controller = new \App\Http\Controllers\LeaveCreditCardController();
    
    echo "=== Testing Employee 338 for August and September 2025 ===\n";
    $result = $controller->getLeaveCredits(338, 2025);
    $data = $result->getData();
    
    if (isset($data->data->beginning_balances)) {
        $balances = $data->data->beginning_balances;
        
        echo "August 2025 (Index 7):\n";
        echo "  VL Balance: " . $balances[7]->vl_balance . "\n";
        echo "  VL With Pay: " . $balances[7]->vl_with_pay . "\n";
        echo "  VL Without Pay: " . $balances[7]->vl_without_pay . "\n";
        echo "  SL With Pay: " . $balances[7]->sl_with_pay . "\n";
        echo "  SL Without Pay: " . $balances[7]->sl_without_pay . "\n";
        
        echo "\nSeptember 2025 (Index 8):\n";
        echo "  VL Balance: " . $balances[8]->vl_balance . "\n";
        echo "  VL With Pay: " . $balances[8]->vl_with_pay . "\n";
        echo "  VL Without Pay: " . $balances[8]->vl_without_pay . "\n";
        echo "  SL With Pay: " . $balances[8]->sl_with_pay . "\n";
        echo "  SL Without Pay: " . $balances[8]->sl_without_pay . "\n";
        
        echo "\nExpected:\n";
        echo "August: Should show 3 days leave (2025-08-27 to 2025-08-29)\n";
        echo "September: Should show 2 days leave (2025-09-03 to 2025-09-05)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
