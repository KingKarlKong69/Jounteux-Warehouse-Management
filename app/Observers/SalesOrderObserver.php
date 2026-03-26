<?php

namespace App\Observers;

use App\Models\Sales_Order;
use App\Services\CacheService;

/**
 * SalesOrderObserver — Event-driven cache invalidation for sales orders.
 *
 * Flushes sales and report caches on any sales order mutation.
 * Critical for ensuring dashboard KPIs and chart data remain fresh.
 */
class SalesOrderObserver
{
    public function created(Sales_Order $salesOrder): void
    {
        CacheService::flushSales();
    }

    public function updated(Sales_Order $salesOrder): void
    {
        // Status transitions (especially to 'completed') affect stock + reports
        CacheService::flushSales();

        // If order was completed, product stock changed — flush products too
        if ($salesOrder->wasChanged('status') && $salesOrder->status === 'completed') {
            CacheService::flushProducts();
        }
    }

    public function deleted(Sales_Order $salesOrder): void
    {
        CacheService::flushSales();
    }

    public function restored(Sales_Order $salesOrder): void
    {
        CacheService::flushSales();
    }
}
