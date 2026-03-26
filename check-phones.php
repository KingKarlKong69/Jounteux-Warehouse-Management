<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$suppliers = App\Models\Supplier::withTrashed()->get(['id', 'phone', 'deleted_at']);
foreach ($suppliers as $s) {
    echo $s->id . ': [' . $s->phone . '] del=' . ($s->deleted_at ?: 'null') . PHP_EOL;
}
