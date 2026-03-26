<?php

namespace App\Observers;

use App\Models\Purchase_Order;
use App\Services\CacheService;

/**
 * PurchaseOrderObserver — Event-driven cache invalidation for purchase orders.
 *
 * Flushes purchase and report caches on any PO mutation.
 * When a PO is completed, product caches are also flushed
 * because stock is added to products.
 */
class PurchaseOrderObserver
{
    public function created(Purchase_Order $purchaseOrder): void
    {
        CacheService::flushPurchases();
    }

    public function updated(Purchase_Order $purchaseOrder): void
    {
        CacheService::flushPurchases();

        // Completed PO adds stock to products — flush product caches
        if ($purchaseOrder->wasChanged('status') && $purchaseOrder->status === 'completed') {
            CacheService::flushProducts();
        }
    }

    public function deleted(Purchase_Order $purchaseOrder): void
    {
        CacheService::flushPurchases();
    }

    public function restored(Purchase_Order $purchaseOrder): void
    {
        CacheService::flushPurchases();
    }
}
