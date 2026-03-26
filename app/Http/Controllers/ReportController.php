<?php

namespace App\Http\Controllers;

use App\Http\Resources\Reports\DailySalesReportResource;
use App\Http\Resources\Reports\InventoryOverviewReportResource;
use App\Http\Resources\Reports\LowStockReportResource;
use App\Http\Resources\Reports\PurchaseOrderReportResource;
use App\Http\Resources\Reports\SupplierProcurementReportResource;
use App\Http\Resources\Reports\TopProductReportResource;
use App\Http\Resources\Reports\UserAnalyticsReportResource;
use App\Models\Products;
use App\Models\Purchase_Order;
use App\Models\Sales_Order;
use App\Models\Sales_Order_Item;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    // ─── Dashboard Page ─────────────────────────────────────────

    /**
     * Render the reports dashboard (Inertia page).
     * Both admin and staff can access; the Vue page adapts per role.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('Admin/Reports/Index', [
            'userRole' => $user->role,
        ]);
    }

    // ─── 1. Daily Sales Summary (Bar Chart) ─────────────────────

    /**
     * Aggregate sales per day for the last N days.
     * Staff: scoped to own sales only.
     * Cached: 5 min TTL, tagged [reports, sales].
     */
    public function dailySales(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $days = min((int) $request->input('days', 30), 365);

        $cacheKey = CacheService::reportKey('daily_sales', $user->role, $user->id, ['days' => $days]);

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_SALES],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () use ($user, $days) {
                $start = Carbon::today()->subDays($days - 1)->startOfDay();

                $query = Sales_Order::query()
                    ->select(
                        DB::raw('DATE(order_date) as date'),
                        DB::raw('SUM(total_amount) as total_amount'),
                        DB::raw('COUNT(*) as order_count')
                    )
                    ->where('order_date', '>=', $start)
                    ->where('status', 'completed');

                if ($user->role === 'staff') {
                    $query->where('created_by', $user->id);
                }

                $data = $query
                    ->groupBy(DB::raw('DATE(order_date)'))
                    ->orderBy('date')
                    ->get();

                return [
                    'data' => DailySalesReportResource::collection($data),
                    'meta' => [
                        'date_range'   => ['from' => $start->toDateString(), 'to' => Carbon::today()->toDateString()],
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 2. Top 10 Best-Selling Products (Pie Chart) ────────────

    /**
     * Top 10 products by sold quantity.
     * Staff: scoped to own sales only.
     * Cached: 5 min TTL, tagged [reports, sales, products].
     */
    public function topProducts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $cacheKey = CacheService::reportKey('top_products', $user->role, $user->id);

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_SALES, CacheService::TAG_PRODUCTS],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () use ($user) {
                $query = Sales_Order_Item::query()
                    ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                    ->join('products', 'sales_order_items.product_id', '=', 'products.id')
                    ->select(
                        'products.id as product_id',
                        'products.name as product_name',
                        DB::raw('SUM(sales_order_items.quantity) as total_quantity'),
                        DB::raw('SUM(sales_order_items.subtotal) as total_revenue')
                    )
                    ->whereNull('sales_orders.deleted_at')
                    ->where('sales_orders.status', 'completed');

                if ($user->role === 'staff') {
                    $query->where('sales_orders.created_by', $user->id);
                }

                return [
                    'data' => TopProductReportResource::collection(
                        $query->groupBy('products.id', 'products.name')
                            ->orderByDesc('total_quantity')
                            ->limit(10)
                            ->get()
                    ),
                    'meta' => ['generated_at' => now()->toIso8601String()],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 3. Inventory Overview (Line Chart) — Admin Only ────────

    /**
     * Product count growth over time, grouped by category.
     * Cached: 5 min TTL, tagged [reports, products].
     */
    public function inventoryOverview(): JsonResponse
    {
        $this->authorizeAdmin();

        $cacheKey = CacheService::reportKey('inventory_overview', 'admin');

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_PRODUCTS],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () {
                $start = Carbon::now()->subMonths(11)->startOfMonth();

                $data = Products::query()
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->select(
                        'categories.name as category',
                        DB::raw("DATE_FORMAT(products.created_at, '%Y-%m') as month"),
                        DB::raw('COUNT(*) as product_count')
                    )
                    ->where('products.created_at', '>=', $start)
                    ->whereNull('products.deleted_at')
                    ->groupBy('categories.name', DB::raw("DATE_FORMAT(products.created_at, '%Y-%m')"))
                    ->orderBy('month')
                    ->get();

                $totals = Products::query()
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->select('categories.name as category', DB::raw('COUNT(*) as total'))
                    ->whereNull('products.deleted_at')
                    ->groupBy('categories.name')
                    ->get();

                return [
                    'data' => [
                        'timeline' => InventoryOverviewReportResource::collection($data),
                        'totals'   => $totals->map(fn ($t) => [
                            'category' => $t->category,
                            'total'    => (int) $t->total,
                        ]),
                    ],
                    'meta' => [
                        'date_range'   => ['from' => $start->toDateString(), 'to' => Carbon::today()->toDateString()],
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 4. User Analytics (Donut Chart) — Admin Only ───────────

    /**
     * User distribution by role + growth over time.
     * Cached: 5 min TTL, tagged [reports, users].
     */
    public function userAnalytics(): JsonResponse
    {
        $this->authorizeAdmin();

        $cacheKey = CacheService::reportKey('user_analytics', 'admin');

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_USERS],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () {
                $distribution = User::query()
                    ->select('role', DB::raw('COUNT(*) as count'))
                    ->whereNull('deleted_at')
                    ->groupBy('role')
                    ->get();

                $start = Carbon::now()->subMonths(11)->startOfMonth();
                $growth = User::query()
                    ->select(
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', $start)
                    ->whereNull('deleted_at')
                    ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
                    ->orderBy('month')
                    ->get();

                $totalUsers   = User::whereNull('deleted_at')->count();
                $blockedUsers = User::whereNull('deleted_at')->where('is_blocked', true)->count();

                return [
                    'data' => UserAnalyticsReportResource::buildResponse(
                        $distribution, $growth, $totalUsers, $blockedUsers
                    ),
                    'meta' => ['generated_at' => now()->toIso8601String()],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 5. Purchase Order Analytics (Stacked Bar) — Admin Only ─

    /**
     * PO volume by status over time.
     * Cached: 5 min TTL, tagged [reports, purchases].
     */
    public function purchaseOrderAnalytics(): JsonResponse
    {
        $this->authorizeAdmin();

        $cacheKey = CacheService::reportKey('po_analytics', 'admin');

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_PURCHASES],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () {
                $start = Carbon::now()->subMonths(11)->startOfMonth();

                $statusOverTime = Purchase_Order::query()
                    ->select(
                        'status',
                        DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(total_amount) as total_amount')
                    )
                    ->where('order_date', '>=', $start)
                    ->whereNull('deleted_at')
                    ->groupBy('status', DB::raw("DATE_FORMAT(order_date, '%Y-%m')"))
                    ->orderBy('month')
                    ->get();

                $statusSummary = Purchase_Order::query()
                    ->select(
                        'status',
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(total_amount) as total_amount')
                    )
                    ->whereNull('deleted_at')
                    ->groupBy('status')
                    ->get();

                $totalValue = Purchase_Order::whereNull('deleted_at')->sum('total_amount');

                return [
                    'data' => PurchaseOrderReportResource::buildResponse(
                        $statusOverTime, $statusSummary, (float) $totalValue
                    ),
                    'meta' => [
                        'date_range'   => ['from' => $start->toDateString(), 'to' => Carbon::today()->toDateString()],
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 6. Low Stock Items (Table) ────────────────────────────

    /**
     * Products where current_stock <= threshold.
     * Available to both admin and staff.
     * Cached: 2 min TTL (critical operational data), tagged [reports, products].
     */
    public function lowStockItems(Request $request): JsonResponse
    {
        $threshold = (int) $request->input('threshold', 10);
        $sortBy    = $request->input('sort', 'current_stock');
        $sortDir   = $request->input('direction', 'asc');

        $allowedSorts = ['current_stock', 'name', 'sku', 'unit_price'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'current_stock';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $cacheKey = CacheService::reportKey('low_stock', 'all', null, [
            'threshold' => $threshold,
            'sort'      => $sortBy,
            'direction' => $sortDir,
        ]);

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_PRODUCTS],
            $cacheKey,
            CacheService::TTL_LOW_STOCK,
            function () use ($threshold, $sortBy, $sortDir) {
                $data = Products::query()
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->select(
                        'products.id',
                        'products.sku',
                        'products.name',
                        'products.current_stock',
                        'products.unit_price',
                        'categories.name as category_name'
                    )
                    ->where('products.current_stock', '<=', $threshold)
                    ->whereNull('products.deleted_at')
                    ->orderBy($sortBy, $sortDir)
                    ->limit(100)
                    ->get();

                return [
                    'data' => LowStockReportResource::collection($data),
                    'meta' => [
                        'threshold'    => $threshold,
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 7. Supplier Procurement Summary (Table) — Admin Only ───

    /**
     * Aggregate PO data per supplier.
     * Cached: 5 min TTL, tagged [reports, suppliers, purchases].
     */
    public function supplierProcurement(): JsonResponse
    {
        $this->authorizeAdmin();

        $cacheKey = CacheService::reportKey('supplier_procurement', 'admin');

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS, CacheService::TAG_SUPPLIERS, CacheService::TAG_PURCHASES],
            $cacheKey,
            CacheService::TTL_REPORT,
            function () {
                $data = Supplier::query()
                    ->leftJoin('purchase_orders', function ($join) {
                        $join->on('suppliers.id', '=', 'purchase_orders.supplier_id')
                             ->whereNull('purchase_orders.deleted_at');
                    })
                    ->select(
                        'suppliers.id',
                        'suppliers.company_name',
                        'suppliers.contact_person',
                        DB::raw('COUNT(purchase_orders.id) as total_orders'),
                        DB::raw('COALESCE(SUM(purchase_orders.total_amount), 0) as total_amount'),
                        DB::raw('MAX(purchase_orders.order_date) as last_order_date')
                    )
                    ->whereNull('suppliers.deleted_at')
                    ->groupBy('suppliers.id', 'suppliers.company_name', 'suppliers.contact_person')
                    ->orderByDesc('total_amount')
                    ->get();

                return [
                    'data' => SupplierProcurementReportResource::collection($data),
                    'meta' => ['generated_at' => now()->toIso8601String()],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── 8. Dashboard Summary Cards ─────────────────────────────

    /**
     * Quick aggregate numbers for the dashboard header cards.
     * Staff gets scoped numbers; admin gets global.
     * Cached: 3 min TTL (dashboard KPIs refresh faster), tagged [reports].
     */
    public function summaryCards(): JsonResponse
    {
        /** @var User $user */
        $user    = Auth::user();
        $isAdmin = $user->role === 'admin';

        $cacheKey = CacheService::summaryCardsKey($user->role, $user->id);

        $result = CacheService::remember(
            [CacheService::TAG_REPORTS],
            $cacheKey,
            CacheService::TTL_SUMMARY,
            function () use ($user, $isAdmin) {
                $salesQuery = Sales_Order::whereNull('deleted_at');
                if (!$isAdmin) {
                    $salesQuery->where('created_by', $user->id);
                }

                $cards = [
                    'total_sales_orders' => (clone $salesQuery)->count(),
                    'total_sales_value'  => round((float) (clone $salesQuery)->where('status', 'completed')->sum('total_amount'), 2),
                    'completed_sales'    => (clone $salesQuery)->where('status', 'completed')->count(),
                ];

                if ($isAdmin) {
                    $cards['total_products']          = Products::whereNull('deleted_at')->count();
                    $cards['low_stock_count']         = Products::whereNull('deleted_at')->where('current_stock', '<=', 10)->count();
                    $cards['total_users']             = User::whereNull('deleted_at')->count();
                    $cards['total_purchase_orders']   = Purchase_Order::whereNull('deleted_at')->count();
                    $cards['total_procurement_value'] = round((float) Purchase_Order::whereNull('deleted_at')->sum('total_amount'), 2);
                    $cards['total_suppliers']         = Supplier::whereNull('deleted_at')->count();
                    $cards['total_customers']         = DB::table('customers')->whereNull('deleted_at')->count();
                }

                return [
                    'data' => $cards,
                    'meta' => [
                        'role'         => $user->role,
                        'generated_at' => now()->toIso8601String(),
                    ],
                ];
            }
        );

        return $this->success($result['data'], $result['meta']);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Throw AuthorizationException if current user is not admin.
     * Handled by global exception handler → 403 JSON response.
     */
    private function authorizeAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            throw new AuthorizationException('Unauthorized. Admin access required.');
        }
    }
}

