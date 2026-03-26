<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ReportController;

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// ─── Report page (Inertia) ────────────────────────────────────
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
});

// ─── Report API v1 (session auth — internal dashboard use) ───
Route::middleware(['auth', 'throttle:60,1'])->prefix('api/v1/reports')->name('api.v1.reports.')->group(function () {
    Route::get('/summary-cards',            [ReportController::class, 'summaryCards'])->name('summary-cards');
    Route::get('/daily-sales',              [ReportController::class, 'dailySales'])->name('daily-sales');
    Route::get('/top-products',             [ReportController::class, 'topProducts'])->name('top-products');
    Route::get('/low-stock-items',          [ReportController::class, 'lowStockItems'])->name('low-stock-items');
    Route::get('/inventory-overview',       [ReportController::class, 'inventoryOverview'])->name('inventory-overview');
    Route::get('/user-analytics',           [ReportController::class, 'userAnalytics'])->name('user-analytics');
    Route::get('/purchase-order-analytics', [ReportController::class, 'purchaseOrderAnalytics'])->name('purchase-order-analytics');
    Route::get('/supplier-procurement',     [ReportController::class, 'supplierProcurement'])->name('supplier-procurement');
});

// ─── Admin routes ─────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard — Enterprise Analytics Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products module
    Route::get('products/export/csv', [\App\Http\Controllers\ProductController::class, 'exportCsv'])->name('products.export.csv');
    Route::get('products/export/excel', [\App\Http\Controllers\ProductController::class, 'exportExcel'])->name('products.export.excel');
    Route::get('products/report', [\App\Http\Controllers\ProductController::class, 'report'])->name('products.report');
    Route::get('products/search', [\App\Http\Controllers\ProductController::class, 'search'])->name('products.search');
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::put('products/{id}/restore', [\App\Http\Controllers\ProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{id}/force-delete', [\App\Http\Controllers\ProductController::class, 'forceDelete'])->name('products.force-delete');

    // Categories
    Route::get('categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{id}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Suppliers
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['create', 'show', 'edit']);
    Route::put('suppliers/{id}/restore', [\App\Http\Controllers\SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::delete('suppliers/{id}/force-delete', [\App\Http\Controllers\SupplierController::class, 'forceDelete'])->name('suppliers.force-delete');
    Route::get('suppliers/export/csv', [\App\Http\Controllers\SupplierController::class, 'exportCsv'])->name('suppliers.export.csv');
    Route::get('suppliers/export/excel', [\App\Http\Controllers\SupplierController::class, 'exportExcel'])->name('suppliers.export.excel');
    Route::get('suppliers/report', [\App\Http\Controllers\SupplierController::class, 'report'])->name('suppliers.report');

    // Purchase Orders
    Route::get('purchase-orders/export/csv', [\App\Http\Controllers\PurchaseOrderController::class, 'exportCsv'])->name('purchase-orders.export.csv');
    Route::get('purchase-orders/export/excel', [\App\Http\Controllers\PurchaseOrderController::class, 'exportExcel'])->name('purchase-orders.export.excel');
    Route::get('purchase-orders/report', [\App\Http\Controllers\PurchaseOrderController::class, 'report'])->name('purchase-orders.report');
    Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->except(['create', 'edit']);

    // Sales Orders
    Route::get('sales-orders/export/csv', [\App\Http\Controllers\SalesOrderController::class, 'exportCsv'])->name('sales-orders.export.csv');
    Route::get('sales-orders/export/excel', [\App\Http\Controllers\SalesOrderController::class, 'exportExcel'])->name('sales-orders.export.excel');
    Route::get('sales-orders/report', [\App\Http\Controllers\SalesOrderController::class, 'report'])->name('sales-orders.report');
    Route::resource('sales-orders', \App\Http\Controllers\SalesOrderController::class)->except(['create', 'edit']);

    // Customers
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->except(['create', 'show', 'edit']);
    Route::put('customers/{id}/restore', [\App\Http\Controllers\CustomerController::class, 'restore'])->name('customers.restore');
    Route::delete('customers/{id}/force-delete', [\App\Http\Controllers\CustomerController::class, 'forceDelete'])->name('customers.force-delete');
    Route::get('customers/export/csv', [\App\Http\Controllers\CustomerController::class, 'exportCsv'])->name('customers.export.csv');
    Route::get('customers/export/excel', [\App\Http\Controllers\CustomerController::class, 'exportExcel'])->name('customers.export.excel');
    Route::get('customers/report', [\App\Http\Controllers\CustomerController::class, 'report'])->name('customers.report');

    // Users & Audit logs
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/block', [\App\Http\Controllers\UserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/unblock', [\App\Http\Controllers\UserController::class, 'unblock'])->name('users.unblock');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs');

    // Stock Ledger (read-only, immutable)
    Route::get('/stock-ledger', [\App\Http\Controllers\StockLedgerController::class, 'index'])->name('stock-ledger.index');
    Route::get('/stock-ledger/export/csv', [\App\Http\Controllers\StockLedgerController::class, 'exportCsv'])->name('stock-ledger.export.csv');
    Route::get('/stock-ledger/export/excel', [\App\Http\Controllers\StockLedgerController::class, 'exportExcel'])->name('stock-ledger.export.excel');
    Route::get('/stock-ledger/report', [\App\Http\Controllers\StockLedgerController::class, 'report'])->name('stock-ledger.report');
});

// ─── Staff routes ─────────────────────────────────────────────
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    // Staff dashboard — Enterprise Analytics Dashboard (adapts per role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products (full CRUD for staff — same as admin)
    Route::get('products/search', [\App\Http\Controllers\StaffProductController::class, 'search'])->name('products.search');
    Route::resource('products', \App\Http\Controllers\StaffProductController::class);
    Route::put('products/{id}/restore', [\App\Http\Controllers\StaffProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{id}/force-delete', [\App\Http\Controllers\StaffProductController::class, 'forceDelete'])->name('products.force-delete');

    // Sales Orders (restricted: create, view, reject only; own orders only)
    Route::get('/sales-orders', [\App\Http\Controllers\StaffSalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::post('/sales-orders', [\App\Http\Controllers\StaffSalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('/sales-orders/{id}', [\App\Http\Controllers\StaffSalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::put('/sales-orders/{id}', [\App\Http\Controllers\StaffSalesOrderController::class, 'update'])->name('sales-orders.update');

    // Customers (full CRUD for staff — same as admin)
    Route::resource('customers', \App\Http\Controllers\StaffCustomerController::class)->except(['create', 'show', 'edit']);
    Route::put('customers/{id}/restore', [\App\Http\Controllers\StaffCustomerController::class, 'restore'])->name('customers.restore');
    Route::delete('customers/{id}/force-delete', [\App\Http\Controllers\StaffCustomerController::class, 'forceDelete'])->name('customers.force-delete');

    // Suppliers (full CRUD for staff — same as admin)
    Route::resource('suppliers', \App\Http\Controllers\StaffSupplierController::class)->except(['create', 'show', 'edit']);
    Route::put('suppliers/{id}/restore', [\App\Http\Controllers\StaffSupplierController::class, 'restore'])->name('suppliers.restore');
    Route::delete('suppliers/{id}/force-delete', [\App\Http\Controllers\StaffSupplierController::class, 'forceDelete'])->name('suppliers.force-delete');
});

// ─── Profile routes ───────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
});

// ─── Notification routes ──────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/clear', [\App\Http\Controllers\NotificationController::class, 'clear']);
});

require __DIR__.'/auth.php';