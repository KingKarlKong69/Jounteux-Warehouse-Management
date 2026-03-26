<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing ProductController Data Output ===" . PHP_EOL . PHP_EOL;

$products = App\Models\Products::latest()->paginate(12);

echo "Paginated products count: " . $products->count() . PHP_EOL;
echo "Total in DB: " . $products->total() . PHP_EOL;
echo "First product: " . ($products->first() ? $products->first()->name : 'none') . PHP_EOL . PHP_EOL;

// Test what ProductResource returns
echo "ProductResource output for first product:" . PHP_EOL;
if ($products->count() > 0) {
    $resource = new App\Http\Resources\ProductResource($products->first());
    print_r($resource->toArray(request()));
    
    echo PHP_EOL . PHP_EOL . "Collection output:" . PHP_EOL;
    $collection = App\Http\Resources\ProductResource::collection($products->items());
    echo "Collection count: " . count($collection) . PHP_EOL;
}

// Test pagination links
echo PHP_EOL . "Pagination structure:" . PHP_EOL;
echo "- Current page: " . $products->currentPage() . PHP_EOL;
echo "- Last page: " . $products->lastPage() . PHP_EOL;
echo "- Per page: " . $products->perPage() . PHP_EOL;
echo "- Total: " . $products->total() . PHP_EOL;
