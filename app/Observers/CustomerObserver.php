<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\CacheService;

/**
 * CustomerObserver — Flushes customer + report caches on mutation.
 */
class CustomerObserver
{
    public function created(Customer $customer): void
    {
        CacheService::flushCustomers();
    }

    public function updated(Customer $customer): void
    {
        CacheService::flushCustomers();
    }

    public function deleted(Customer $customer): void
    {
        CacheService::flushCustomers();
    }

    public function restored(Customer $customer): void
    {
        CacheService::flushCustomers();
    }
}
