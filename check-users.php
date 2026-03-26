<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Users ===" . PHP_EOL . PHP_EOL;

$users = App\Models\User::all(['id', 'name', 'email', 'role']);

if ($users->isEmpty()) {
    echo "No users found in the database!" . PHP_EOL;
} else {
    foreach ($users as $user) {
        echo "ID: {$user->id}" . PHP_EOL;
        echo "Name: {$user->name}" . PHP_EOL;
        echo "Email: {$user->email}" . PHP_EOL;
        echo "Role: " . ($user->role ?? 'NULL/NOT SET') . PHP_EOL;
        echo "---" . PHP_EOL;
    }
}
