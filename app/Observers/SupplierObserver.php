<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Services\CacheService;

/**
 * SupplierObserver — Flushes supplier + report caches on mutation.
 */
class SupplierObserver
{
    public function created(Supplier $supplier): void
    {
        CacheService::flushSuppliers();
    }

    public function updated(Supplier $supplier): void
    {
        CacheService::flushSuppliers();
    }

    public function deleted(Supplier $supplier): void
    {
        CacheService::flushSuppliers();
    }

    public function restored(Supplier $supplier): void
    {
        CacheService::flushSuppliers();
    }
}
