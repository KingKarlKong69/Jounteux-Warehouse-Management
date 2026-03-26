<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ─────────────────────────────────────────────────────────────
 * Performance Indexes Migration
 * ─────────────────────────────────────────────────────────────
 *
 * Adds compound and single-column indexes tailored to the
 * actual query patterns used by the Report API, dashboard,
 * and list/search controllers. Each index is documented with
 * the query or endpoint it optimizes.
 *
 * Naming convention: idx_{table}_{column(s)}
 *
 * @see App\Http\Controllers\ReportController
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sales_orders ────────────────────────────────────────
        Schema::table('sales_orders', function (Blueprint $table) {
            // dailySales: WHERE status='completed' AND deleted_at IS NULL AND order_date >= ?
            // summaryCards: WHERE status='completed' AND deleted_at IS NULL
            $table->index(['status', 'deleted_at', 'order_date'], 'idx_so_status_deleted_date');

            // dailySales staff scope: WHERE created_by = ? AND status = 'completed'
            $table->index(['created_by', 'status'], 'idx_so_createdby_status');

            // order_date for range scans (GROUP BY DATE(order_date))
            $table->index('order_date', 'idx_so_order_date');
        });

        // ── purchase_orders ─────────────────────────────────────
        Schema::table('purchase_orders', function (Blueprint $table) {
            // purchaseOrderAnalytics: WHERE order_date >= ? AND deleted_at IS NULL GROUP BY status
            $table->index(['status', 'deleted_at', 'order_date'], 'idx_po_status_deleted_date');

            // order_date for range scans
            $table->index('order_date', 'idx_po_order_date');
        });

        // ── products ────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            // lowStockItems: WHERE current_stock <= ? AND deleted_at IS NULL
            // summaryCards: WHERE current_stock <= 10 AND deleted_at IS NULL
            $table->index(['deleted_at', 'current_stock'], 'idx_prod_deleted_stock');

            // inventoryOverview: WHERE created_at >= ? AND deleted_at IS NULL GROUP BY category_id
            $table->index(['deleted_at', 'category_id', 'created_at'], 'idx_prod_deleted_cat_created');
        });

        // ── stock_ledgers ───────────────────────────────────────
        Schema::table('stock_ledgers', function (Blueprint $table) {
            // Time-series product history: WHERE product_id = ? ORDER BY created_at
            $table->index(['product_id', 'created_at'], 'idx_sl_product_created');

            // Filter by movement direction per product
            $table->index(['product_id', 'movement_type'], 'idx_sl_product_movement');

            // Filter by reference source
            $table->index('reference_type', 'idx_sl_reference_type');
        });

        // ── customers ───────────────────────────────────────────
        Schema::table('customers', function (Blueprint $table) {
            // summaryCards & all list queries: whereNull('deleted_at')
            $table->index('deleted_at', 'idx_cust_deleted');

            // Search/filter by email
            $table->index('email', 'idx_cust_email');
        });

        // ── suppliers ───────────────────────────────────────────
        Schema::table('suppliers', function (Blueprint $table) {
            // supplierProcurement & list queries: whereNull('deleted_at')
            $table->index('deleted_at', 'idx_sup_deleted');

            // Search by company name
            $table->index('company_name', 'idx_sup_company_name');
        });

        // ── users ───────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // userAnalytics: GROUP BY role WHERE deleted_at IS NULL
            $table->index(['deleted_at', 'role'], 'idx_user_deleted_role');

            // userAnalytics: WHERE is_blocked = true
            $table->index('is_blocked', 'idx_user_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('idx_so_status_deleted_date');
            $table->dropIndex('idx_so_createdby_status');
            $table->dropIndex('idx_so_order_date');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('idx_po_status_deleted_date');
            $table->dropIndex('idx_po_order_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_prod_deleted_stock');
            $table->dropIndex('idx_prod_deleted_cat_created');
        });

        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_sl_product_created');
            $table->dropIndex('idx_sl_product_movement');
            $table->dropIndex('idx_sl_reference_type');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_cust_deleted');
            $table->dropIndex('idx_cust_email');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_sup_deleted');
            $table->dropIndex('idx_sup_company_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_user_deleted_role');
            $table->dropIndex('idx_user_blocked');
        });
    }
};
