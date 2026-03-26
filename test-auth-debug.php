<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Testing Authentication Flow ===" . PHP_EOL . PHP_EOL;

// Test route exists
echo "1. Checking if routes exist:" . PHP_EOL;
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$adminDashboard = $routes->getByName('admin.dashboard');
$staffDashboard = $routes->getByName('staff.dashboard');

echo "   - admin.dashboard: " . ($adminDashboard ? "✓ EXISTS" : "✗ NOT FOUND") . PHP_EOL;
echo "   - staff.dashboard: " . ($staffDashboard ? "✓ EXISTS" : "✗ NOT FOUND") . PHP_EOL;

if ($adminDashboard) {
    echo "   - URI: " . $adminDashboard->uri() . PHP_EOL;
    echo "   - Middleware: " . implode(', ', $adminDashboard->middleware()) . PHP_EOL;
}

echo PHP_EOL . "2. Checking user with role 'admin':" . PHP_EOL;
$adminUser = App\Models\User::where('role', 'admin')->first();
if ($adminUser) {
    echo "   ✓ Admin user found: {$adminUser->email}" . PHP_EOL;
    echo "   - ID: {$adminUser->id}" . PHP_EOL;
    echo "   - Name: {$adminUser->name}" . PHP_EOL;
    echo "   - Role: {$adminUser->role}" . PHP_EOL;
} else {
    echo "   ✗ No admin user found!" . PHP_EOL;
}

echo PHP_EOL . "3. Checking middleware registration:" . PHP_EOL;
$middleware = app()->make('Illuminate\Contracts\Http\Kernel');
echo "   - RoleMiddleware registered: " . (class_exists('App\Http\Middleware\RoleMiddleware') ? "✓ YES" : "✗ NO") . PHP_EOL;

echo PHP_EOL . "4. Testing Inertia share data:" . PHP_EOL;
echo "   - HandleInertiaRequests: " . (class_exists('App\Http\Middleware\HandleInertiaRequests') ? "✓ EXISTS" : "✗ NOT FOUND") . PHP_EOL;
