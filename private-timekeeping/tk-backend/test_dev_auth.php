<?php

/**
 * Dev Authentication Test Script
 * 
 * This script tests the dev authentication functionality
 * Run this from the backend directory: php test_dev_auth.php
 */

require_once 'vendor/autoload.php';

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Dev Authentication Test ===\n\n";

// Test 1: Check if admin user exists
echo "1. Checking for admin user...\n";
$adminUser = User::where('is_admin', 1)->first();

if (!$adminUser) {
    echo "❌ FAIL: No admin user found with is_admin = 1\n";
    echo "   Please create an admin user in the database.\n\n";
    exit(1);
}

echo "✅ PASS: Admin user found\n";
echo "   - ID: {$adminUser->id}\n";
echo "   - Name: {$adminUser->name}\n";
echo "   - Email: {$adminUser->email}\n";
echo "   - Employee No: {$adminUser->employee_no}\n";
echo "   - Is Admin: " . ($adminUser->is_admin ? 'Yes' : 'No') . "\n";
echo "   - Active: " . ($adminUser->active ? 'Yes' : 'No') . "\n";
echo "   - Locked: " . ($adminUser->locked ? 'Yes' : 'No') . "\n\n";

// Test 2: Check user status
echo "2. Checking admin user status...\n";
$issues = [];

if (isset($adminUser->active) && (int) $adminUser->active === 0) {
    $issues[] = "User is inactive";
}

if ($adminUser->locked) {
    $issues[] = "User is locked";
}

if ($adminUser->with_expiration && $adminUser->expiration_date <= now()) {
    $issues[] = "User has expired";
}

if (empty($issues)) {
    echo "✅ PASS: Admin user status is valid\n\n";
} else {
    echo "❌ FAIL: Admin user has issues:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
    echo "\n";
}

// Test 3: Check environment configuration
echo "3. Checking environment configuration...\n";
$appEnv = app()->environment();
$devAuthEnabled = env('DEV_AUTH_ENABLED', false);

echo "   - App Environment: {$appEnv}\n";
echo "   - Dev Auth Enabled: " . ($devAuthEnabled ? 'Yes' : 'No') . "\n";

if (in_array($appEnv, ['local', 'development', 'dev']) && $devAuthEnabled) {
    echo "✅ PASS: Environment configuration is correct\n\n";
} else {
    echo "❌ FAIL: Environment configuration issues:\n";
    if (!in_array($appEnv, ['local', 'development', 'dev'])) {
        echo "   - App environment '{$appEnv}' is not development\n";
    }
    if (!$devAuthEnabled) {
        echo "   - DEV_AUTH_ENABLED is not set to true\n";
    }
    echo "\n";
}

// Test 4: Test token generation
echo "4. Testing token generation...\n";
try {
    $token = $adminUser->createToken('dev-test-token')->plainTextToken ?? 
            hash('sha256', $adminUser->id . time() . config('app.key'));
    
    if ($token) {
        echo "✅ PASS: Token generation successful\n";
        echo "   - Token: " . substr($token, 0, 20) . "...\n\n";
    } else {
        echo "❌ FAIL: Token generation failed\n\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Token generation error: " . $e->getMessage() . "\n\n";
}

// Test 5: Check middleware registration
echo "5. Checking middleware registration...\n";
$kernel = app('Illuminate\Contracts\Http\Kernel');
$middleware = $kernel->getMiddleware();

$devAuthFound = false;
foreach ($middleware as $middlewareClass) {
    if (strpos($middlewareClass, 'DevAuth') !== false) {
        $devAuthFound = true;
        break;
    }
}

if ($devAuthFound) {
    echo "✅ PASS: DevAuth middleware is registered\n\n";
} else {
    echo "❌ FAIL: DevAuth middleware not found in global middleware\n\n";
}

// Test 6: Check API route
echo "6. Checking API route registration...\n";
$routes = app('router')->getRoutes();
$devLoginRouteFound = false;

foreach ($routes as $route) {
    if ($route->uri() === 'api/dev-login' && $route->methods()[0] === 'POST') {
        $devLoginRouteFound = true;
        break;
    }
}

if ($devLoginRouteFound) {
    echo "✅ PASS: /api/dev-login route is registered\n\n";
} else {
    echo "❌ FAIL: /api/dev-login route not found\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Dev authentication setup appears to be " . 
     (empty($issues) && $devAuthFound && $devLoginRouteFound ? "✅ WORKING" : "❌ NOT WORKING") . "\n\n";

if (empty($issues) && $devAuthFound && $devLoginRouteFound) {
    echo "🎉 Dev authentication is ready to use!\n";
    echo "   - Start your backend server\n";
    echo "   - Start your frontend server\n";
    echo "   - Access any module - authentication will happen automatically\n";
} else {
    echo "🔧 Please fix the issues above before using dev authentication.\n";
}

echo "\n";
