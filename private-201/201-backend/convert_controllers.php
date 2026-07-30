<?php

/**
 * Controller Conversion Script
 * 
 * This script helps convert Laravel controllers from returning views to returning JSON API responses.
 * Run this script to get a list of all controllers that need conversion and their current methods.
 */

$controllersDir = __DIR__ . '/app/Http/Controllers/';
$controllers = [];

// Get all controller files
$files = glob($controllersDir . '*.php');

foreach ($files as $file) {
    $filename = basename($file);
    $className = str_replace('.php', '', $filename);
    
    if ($className === 'Controller') {
        continue; // Skip base controller
    }
    
    $content = file_get_contents($file);
    
    // Check if controller already uses ApiResponse trait
    $hasApiResponse = strpos($content, 'use App\\Traits\\ApiResponse') !== false;
    
    // Find methods that return views
    preg_match_all('/public function (\w+)\([^)]*\)\s*\{[^}]*return view\([^}]*\}/s', $content, $viewMethods);
    
    // Find methods that return redirects
    preg_match_all('/public function (\w+)\([^)]*\)\s*\{[^}]*return (?:back\(\)|redirect\(\))[^}]*\}/s', $content, $redirectMethods);
    
    $methods = [];
    
    // Add view methods
    if (!empty($viewMethods[1])) {
        foreach ($viewMethods[1] as $method) {
            $methods[] = [
                'name' => $method,
                'type' => 'view',
                'status' => 'needs_conversion'
            ];
        }
    }
    
    // Add redirect methods
    if (!empty($redirectMethods[1])) {
        foreach ($redirectMethods[1] as $method) {
            $methods[] = [
                'name' => $method,
                'type' => 'redirect',
                'status' => 'needs_conversion'
            ];
        }
    }
    
    if (!empty($methods)) {
        $controllers[] = [
            'name' => $className,
            'file' => $filename,
            'has_api_response' => $hasApiResponse,
            'methods' => $methods
        ];
    }
}

// Sort controllers by priority
usort($controllers, function($a, $b) {
    // Priority 1: Simple CRUD controllers
    $priority1 = ['SSSController', 'TaxController', 'GSISController', 'SalaryGradeController', 
                  'SalaryStepController', 'SalarySchedulesController', 'SectionsController', 
                  'ServiceRecordController', 'ShiftScheduleController', 'StepIncrementController',
                  'SubsistenceController', 'UniformClothingSetupController', 'Update201ScheduleController',
                  'WorkCancellationController', 'YearEndController', 'TravelAbroadEndorsementReportController',
                  'TimeKeepingSetupController', 'TerminalLeaveEndorsementReportController',
                  'SemesterRatingController', 'PromotionTypeController'];
    
    // Priority 2: Complex controllers
    $priority2 = ['AccessRightsController', 'InterviewController', 'OvertimeApplicationController',
                  'PlantillaReportController', 'PlantillasController', 'ProcessAttendanceController',
                  'RatingController', 'ReimbursementCommunicationExpensesController', 'SalaryAdjustmentController',
                  'SALNController', 'UsersController', 'VacantPositionController', 'YearEndBonusController',
                  'UniformClothingController', 'TrainingRequisitionersController', 'TrainingRequisitionController',
                  'TradionessReportController', 'RATAController', 'PositionsController'];
    
    $aPriority = array_search($a['name'], $priority1);
    $bPriority = array_search($b['name'], $priority1);
    
    if ($aPriority !== false && $bPriority !== false) {
        return $aPriority - $bPriority;
    }
    
    if ($aPriority !== false) return -1;
    if ($bPriority !== false) return 1;
    
    $aPriority = array_search($a['name'], $priority2);
    $bPriority = array_search($b['name'], $priority2);
    
    if ($aPriority !== false && $bPriority !== false) {
        return $aPriority - $bPriority;
    }
    
    if ($aPriority !== false) return -1;
    if ($bPriority !== false) return 1;
    
    return strcmp($a['name'], $b['name']);
});

echo "=== CONTROLLER CONVERSION REPORT ===\n\n";

echo "Total controllers needing conversion: " . count($controllers) . "\n\n";

$totalMethods = 0;
$convertedControllers = 0;

foreach ($controllers as $controller) {
    $totalMethods += count($controller['methods']);
    if ($controller['has_api_response']) {
        $convertedControllers++;
    }
}

echo "Total methods to convert: " . $totalMethods . "\n";
echo "Controllers already converted: " . $convertedControllers . "\n";
echo "Controllers remaining: " . (count($controllers) - $convertedControllers) . "\n\n";

echo "=== DETAILED BREAKDOWN ===\n\n";

foreach ($controllers as $controller) {
    $status = $controller['has_api_response'] ? '✅ CONVERTED' : '❌ NEEDS CONVERSION';
    echo "{$controller['name']} ({$controller['file']}) - {$status}\n";
    
    foreach ($controller['methods'] as $method) {
        echo "  - {$method['name']}() ({$method['type']} return)\n";
    }
    echo "\n";
}

echo "=== CONVERSION COMMANDS ===\n\n";

echo "To convert all controllers, run these commands:\n\n";

foreach ($controllers as $controller) {
    if (!$controller['has_api_response']) {
        echo "# Convert {$controller['name']}\n";
        echo "php artisan make:command Convert{$controller['name']}Command\n";
        echo "# Then implement the conversion logic\n\n";
    }
}

echo "=== MANUAL CONVERSION STEPS ===\n\n";

echo "For each controller:\n";
echo "1. Add: use App\\Traits\\ApiResponse;\n";
echo "2. Add: use ApiResponse; inside the class\n";
echo "3. Wrap methods in try-catch blocks\n";
echo "4. Replace view() returns with successResponse()\n";
echo "5. Replace redirect() returns with appropriate JSON responses\n";
echo "6. Add proper error handling\n";
echo "7. Add missing CRUD methods (show, destroy) if needed\n\n";

echo "=== API RESPONSE EXAMPLES ===\n\n";

echo "// Success response\n";
echo "return \$this->successResponse(\$data, 'Data retrieved successfully');\n\n";

echo "// Error response\n";
echo "return \$this->errorResponse('Operation failed');\n\n";

echo "// Validation error\n";
echo "return \$this->validationErrorResponse(\$validator->errors());\n\n";

echo "// Not found\n";
echo "return \$this->notFoundResponse('Record not found');\n\n";

echo "// Server error\n";
echo "return \$this->serverErrorResponse('Internal server error');\n\n";

echo "=== NEXT STEPS ===\n\n";

echo "1. Review the conversion guide in API_CONVERSION_GUIDE.md\n";
echo "2. Convert controllers in priority order\n";
echo "3. Test each conversion thoroughly\n";
echo "4. Update routes if needed\n";
echo "5. Update frontend to consume JSON instead of views\n";
echo "6. Remove view files once no longer needed\n\n";

echo "=== COMPLETED CONVERSIONS ===\n\n";

$converted = ['PhilhealthController', 'ReligionsController', 'SSSController'];

foreach ($converted as $name) {
    echo "✅ {$name} - CONVERTED\n";
}

echo "\n=== REMAINING CONVERSIONS ===\n\n";

$remaining = array_filter($controllers, function($c) {
    return !$c['has_api_response'] && !in_array($c['name'], ['PhilhealthController', 'ReligionsController', 'SSSController']);
});

foreach ($remaining as $controller) {
    echo "❌ {$controller['name']} - NEEDS CONVERSION\n";
    foreach ($controller['methods'] as $method) {
        echo "  - {$method['name']}() ({$method['type']} return)\n";
    }
    echo "\n";
} 