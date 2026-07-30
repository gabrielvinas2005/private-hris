<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Leave Credit Card Pagination...\n";

try {
    $controller = new \App\Http\Controllers\LeaveCreditCardController();
    
    // Test 1: Basic pagination
    echo "\n=== Test 1: Basic Pagination (Page 1, 10 per page) ===\n";
    $request1 = new \Illuminate\Http\Request(['page' => 1, 'per_page' => 10]);
    $result1 = $controller->index($request1);
    $data1 = $result1->getData();
    
    echo "Total employees: " . $data1->pagination->total . "\n";
    echo "Current page: " . $data1->pagination->current_page . "\n";
    echo "Per page: " . $data1->pagination->per_page . "\n";
    echo "Last page: " . $data1->pagination->last_page . "\n";
    echo "Showing: " . $data1->pagination->from . " to " . $data1->pagination->to . "\n";
    echo "Data count: " . count($data1->data) . "\n";
    
    // Test 2: Different page size
    echo "\n=== Test 2: Different Page Size (Page 1, 5 per page) ===\n";
    $request2 = new \Illuminate\Http\Request(['page' => 1, 'per_page' => 5]);
    $result2 = $controller->index($request2);
    $data2 = $result2->getData();
    
    echo "Per page: " . $data2->pagination->per_page . "\n";
    echo "Data count: " . count($data2->data) . "\n";
    
    // Test 3: Search functionality
    echo "\n=== Test 3: Search Functionality ===\n";
    $request3 = new \Illuminate\Http\Request(['page' => 1, 'per_page' => 10, 'search' => 'admin']);
    $result3 = $controller->index($request3);
    $data3 = $result3->getData();
    
    echo "Search results for 'admin': " . $data3->pagination->total . " found\n";
    echo "Data count: " . count($data3->data) . "\n";
    
    if (count($data3->data) > 0) {
        echo "First result: " . $data3->data[0]->name . "\n";
    }
    
    // Test 4: Page 2
    echo "\n=== Test 4: Page 2 ===\n";
    $request4 = new \Illuminate\Http\Request(['page' => 2, 'per_page' => 5]);
    $result4 = $controller->index($request4);
    $data4 = $result4->getData();
    
    echo "Page: " . $data4->pagination->current_page . "\n";
    echo "Showing: " . $data4->pagination->from . " to " . $data4->pagination->to . "\n";
    echo "Data count: " . count($data4->data) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

