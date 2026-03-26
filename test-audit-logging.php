<?php

/**
 * Test Audit Logging Functionality
 * 
 * Run this file with: php test-audit-logging.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Products;
use App\Models\Audit_Logs;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Audit Logging Functionality ===\n\n";

// Test 1: Check if audit_logs table exists and is accessible
echo "Test 1: Checking audit_logs table...\n";
try {
    $count = Audit_Logs::count();
    echo "✓ Audit logs table exists. Current count: {$count}\n\n";
} catch (\Exception $e) {
    echo "✗ Error accessing audit_logs table: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Test manual audit log creation
echo "Test 2: Creating a manual audit log entry...\n";
try {
    $user = User::first();
    if (!$user) {
        echo "✗ No users found in database. Please create a user first.\n\n";
    } else {
        $auditLog = Audit_Logs::create([
            'user_id' => $user->id,
            'action' => 'test',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
        ]);
        echo "✓ Manual audit log created with ID: {$auditLog->id}\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error creating manual audit log: " . $e->getMessage() . "\n\n";
}

// Test 3: Test Auditable trait by creating a product
echo "Test 3: Testing Auditable trait with product creation...\n";
try {
    $user = User::first();
    if ($user) {
        Auth::login($user);
        echo "✓ Authenticated as user: {$user->email}\n";
        
        $product = Products::create([
            'sku' => 'TEST-' . time(),
            'name' => 'Test Product for Audit',
            'description' => 'This is a test product',
            'unit_price' => 99.99,
            'current_stock' => 10,
        ]);
        
        echo "✓ Product created with ID: {$product->id}\n";
        
        // Wait a moment for event to process
        sleep(1);
        
        // Check if audit log was created
        $auditLog = Audit_Logs::where('auditable_type', Products::class)
                              ->where('auditable_id', $product->id)
                              ->where('action', 'created')
                              ->first();
        
        if ($auditLog) {
            echo "✓ Audit log created for product! Log ID: {$auditLog->id}\n";
            echo "  - Action: {$auditLog->action}\n";
            echo "  - User ID: {$auditLog->user_id}\n";
            echo "  - IP: {$auditLog->ip_address}\n";
        } else {
            echo "✗ No audit log found for product creation\n";
            echo "  This might indicate an issue with the Auditable trait or event listeners\n";
        }
        
        // Clean up test product
        $product->forceDelete();
        echo "✓ Test product deleted\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error testing Auditable trait: " . $e->getMessage() . "\n\n";
}

// Test 4: Display all audit logs
echo "Test 4: Displaying all audit logs...\n";
try {
    $logs = Audit_Logs::with('user')->latest()->take(10)->get();
    echo "Total audit logs: " . Audit_Logs::count() . "\n";
    echo "Recent logs (last 10):\n\n";
    
    foreach ($logs as $log) {
        $userName = $log->user ? $log->user->name : 'System';
        echo "  [{$log->created_at}] {$userName} - {$log->action}";
        if ($log->auditable_type) {
            echo " on " . class_basename($log->auditable_type) . " #{$log->auditable_id}";
        }
        echo "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error displaying audit logs: " . $e->getMessage() . "\n\n";
}

echo "=== Testing Complete ===\n";
echo "\nTo test login/logout audit logging:\n";
echo "1. Visit http://localhost:8000/login\n";
echo "2. Log in with valid credentials\n";
echo "3. Log out\n";
echo "4. Run: php artisan tinker --execute=\"\App\Models\Audit_Logs::latest()->take(5)->get()->each(function(\$log) { echo \$log->action . ' - ' . \$log->created_at . PHP_EOL; })\"\n";
