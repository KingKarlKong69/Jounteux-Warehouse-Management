<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate a web request to check session
$request = Illuminate\Http\Request::create('/admin/dashboard', 'GET');
$request->setLaravelSession(app('session.store'));

echo "=== Checking Auth Session ===" . PHP_EOL . PHP_EOL;

// Start session
$session = app('session.store');
$session->start();

echo "Session ID: " . $session->getId() . PHP_EOL;
echo "Session has user: " . ($session->has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d') ? 'YES' : 'NO') . PHP_EOL;

// Check if Auth works
if (auth()->check()) {
    echo "Auth check: YES" . PHP_EOL;
    echo "User: " . auth()->user()->name . PHP_EOL;
    echo "Email: " . auth()->user()->email . PHP_EOL;
    echo "Role: " . auth()->user()->role . PHP_EOL;
} else {
    echo "Auth check: NO" . PHP_EOL;
    echo "⚠️ No authenticated user found!" . PHP_EOL;
}
