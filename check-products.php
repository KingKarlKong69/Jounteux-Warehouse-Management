<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Products in Database ===" . PHP_EOL . PHP_EOL;

$count = App\Models\Products::count();
echo "Total products: " . $count . PHP_EOL . PHP_EOL;

if ($count > 0) {
    echo "Sample products:" . PHP_EOL;
    $products = App\Models\Products::limit(5)->get(['id', 'sku', 'name', 'unit_price', 'current_stock']);
    foreach($products as $p) {
        echo "  - ID: {$p->id} | SKU: {$p->sku} | Name: {$p->name} | Price: {$p->unit_price} | Stock: {$p->current_stock}" . PHP_EOL;
    }
} else {
    echo "⚠️ NO PRODUCTS IN DATABASE!" . PHP_EOL;
    echo "You need to add products first." . PHP_EOL;
}
