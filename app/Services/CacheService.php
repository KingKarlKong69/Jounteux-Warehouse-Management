<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ─────────────────────────────────────────────────────────────
 * CacheService — Centralized Caching Abstraction Layer
 * ─────────────────────────────────────────────────────────────
 *
 * Provides a single source of truth for all cache key naming,
 * TTL values, and cache invalidation. Eliminates scattered
 * Cache::remember() calls across the codebase.
 *
 * Invalidation Strategy (works with ANY cache driver):
 *   Each domain (products, sales, etc.) has a "version" counter
 *   stored in cache. All cache keys embed their domain versions.
 *   To invalidate a domain, we increment its version — all old
 *   keys become unreachable and expire naturally via TTL.
 *
 *   This is an O(1) invalidation approach that requires no
 *   key tracking, no Redis tags, and works with database/file
 *   cache drivers out of the box.
 *
 * Architecture:
 *   - Version-based domain groups: reports, products, orders, users
 *   - Role-scoped keys: admin vs staff see different data
 *   - Event-driven invalidation via Model Observers
 *
 * @see \App\Observers\ProductObserver
 * @see \App\Observers\SalesOrderObserver
 * @see \App\Observers\PurchaseOrderObserver
 */
class CacheService
{
    // ─── TTL Constants (seconds) ────────────────────────────────

    /** Heavy aggregation reports — 5 minutes */
    const TTL_REPORT = 300;

    /** Summary cards — 3 minutes (dashboard KPIs refresh faster) */
    const TTL_SUMMARY = 180;

    /** Low stock alerts — 2 minutes (critical operational data) */
    const TTL_LOW_STOCK = 120;

    /** Product listing pages — 5 minutes */
    const TTL_PRODUCT_LIST = 300;

    /** Domain version keys never expire (forever) */
    const TTL_VERSION = 0;

    // ─── Domain Tags (used as version key prefixes) ─────────────

    const TAG_REPORTS      = 'reports';
    const TAG_PRODUCTS     = 'products';
    const TAG_SALES        = 'sales';
    const TAG_PURCHASES    = 'purchases';
    const TAG_USERS        = 'users';
    const TAG_SUPPLIERS    = 'suppliers';
    const TAG_CUSTOMERS    = 'customers';

    // ─── Version Management ─────────────────────────────────────

    /**
     * Get the current version number for a domain.
     * Returns 1 if no version exists yet.
     */
    private static function domainVersion(string $domain): int
    {
        return (int) Cache::get("domain_version:{$domain}", 1);
    }

    /**
     * Increment the version for a domain, effectively invalidating
     * all cache entries that embed this domain's version.
     */
    private static function incrementVersion(string $domain): void
    {
        $key = "domain_version:{$domain}";
        $new = self::domainVersion($domain) + 1;
        Cache::forever($key, $new);

        Log::debug("CacheService: Invalidated domain [{$domain}] → version {$new}");
    }

    /**
     * Build a versioned prefix from an array of domain tags.
     * Example: tags [reports, sales] with versions [3, 7] → "reports.3:sales.7:"
     */
    private static function versionedPrefix(array $tags): string
    {
        $parts = [];
        foreach ($tags as $tag) {
            $parts[] = $tag . '.' . self::domainVersion($tag);
        }
        sort($parts); // deterministic ordering
        return implode(':', $parts) . ':';
    }

    // ─── Cache Key Builders ─────────────────────────────────────

    /**
     * Generate a namespaced cache key for report endpoints.
     *
     * @param string      $report   e.g. 'daily_sales', 'top_products'
     * @param string      $role     'admin' or 'staff'
     * @param int|null    $userId   Staff user ID for scoped data
     * @param array       $params   Additional query params (days, threshold, etc.)
     */
    public static function reportKey(string $report, string $role, ?int $userId = null, array $params = []): string
    {
        $base = "report:{$report}:role:{$role}";

        if ($role === 'staff' && $userId) {
            $base .= ":user:{$userId}";
        }

        if (!empty($params)) {
            ksort($params);
            $base .= ':' . md5(serialize($params));
        }

        return $base;
    }

    /**
     * Generate cache key for summary cards.
     */
    public static function summaryCardsKey(string $role, ?int $userId = null): string
    {
        return self::reportKey('summary_cards', $role, $userId);
    }

    // ─── Cache Operations ───────────────────────────────────────

    /**
     * Remember a value with domain-version-scoped key and TTL.
     *
     * Works with ANY cache driver (database, file, redis, etc.).
     * The tags array determines which domain versions are embedded
     * in the key — when any domain is invalidated, the old key
     * becomes unreachable and expires via its natural TTL.
     *
     * @param array    $tags     Domain groups for invalidation scoping
     * @param string   $key      Logical cache key (will be prefixed)
     * @param int      $ttl      Seconds until expiry
     * @param callable $callback Closure that produces the cached value
     * @return mixed
     */
    public static function remember(array $tags, string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = self::versionedPrefix($tags) . $key;

        return Cache::remember($fullKey, $ttl, $callback);
    }

    // ─── Cache Invalidation ─────────────────────────────────────

    /**
     * Flush all report caches.
     * Called when any data affecting reports changes.
     */
    public static function flushReports(): void
    {
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush product-related caches (reports + product lists).
     * Called by ProductObserver on create/update/delete/restore.
     */
    public static function flushProducts(): void
    {
        self::incrementVersion(self::TAG_PRODUCTS);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush sales-related caches.
     * Called by SalesOrderObserver on create/update/delete.
     */
    public static function flushSales(): void
    {
        self::incrementVersion(self::TAG_SALES);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush purchase-related caches.
     * Called by PurchaseOrderObserver on create/update/delete.
     */
    public static function flushPurchases(): void
    {
        self::incrementVersion(self::TAG_PURCHASES);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush user-related caches.
     */
    public static function flushUsers(): void
    {
        self::incrementVersion(self::TAG_USERS);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush supplier-related caches.
     */
    public static function flushSuppliers(): void
    {
        self::incrementVersion(self::TAG_SUPPLIERS);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Flush customer-related caches.
     */
    public static function flushCustomers(): void
    {
        self::incrementVersion(self::TAG_CUSTOMERS);
        self::incrementVersion(self::TAG_REPORTS);
    }

    /**
     * Nuclear option — flush ALL application caches.
     * Increments every domain version.
     */
    public static function flushAll(): void
    {
        $domains = [
            self::TAG_REPORTS,
            self::TAG_PRODUCTS,
            self::TAG_SALES,
            self::TAG_PURCHASES,
            self::TAG_USERS,
            self::TAG_SUPPLIERS,
            self::TAG_CUSTOMERS,
        ];

        foreach ($domains as $domain) {
            self::incrementVersion($domain);
        }
    }
}
