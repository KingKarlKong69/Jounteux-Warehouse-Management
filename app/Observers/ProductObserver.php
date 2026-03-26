<?php

namespace App\Observers;

use App\Models\Products;
use App\Services\CacheService;

/**
 * ProductObserver — Event-driven cache invalidation for products.
 *
 * Automatically flushes product and report caches whenever
 * products are created, updated, deleted, or restored.
 * Registered in AppServiceProvider::boot().
 */
class ProductObserver
{
    public function created(Products $product): void
    {
        CacheService::flushProducts();
    }

    public function updated(Products $product): void
    {
        CacheService::flushProducts();
    }

    public function deleted(Products $product): void
    {
        CacheService::flushProducts();
    }

    public function restored(Products $product): void
    {
        CacheService::flushProducts();
    }

    public function forceDeleted(Products $product): void
    {
        CacheService::flushProducts();
    }
}
