<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSalesOrderRequest;
use Inertia\Inertia;
use App\Models\Sales_Order;
use App\Models\Sales_Order_Item;
use App\Models\Products;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Stock_Ledger;
use App\Http\Resources\SalesOrderResource;
use App\Jobs\SendSalesOrderCompletedEmail;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\HasIndexFilters;
use App\Traits\HasExport;

class SalesOrderController extends Controller
{
    use HasIndexFilters, HasExport;

    /**
     * Display listing of sales orders.
     */
    public function index(Request $request)
    {
        $query = Sales_Order::with(['customer', 'creator'])
            ->withCount('items');

        $this->applySearch($query, $request->input('search'), ['so_number'], ['customer' => 'customer_name']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $this->applySort($query, $request, ['so_number', 'order_date', 'delivery_date', 'total_amount', 'status', 'created_at']);

        $salesOrders = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/SalesOrders/Index', [
            'salesOrders' => [
                'data' => SalesOrderResource::collection($salesOrders->items())->toArray(request()),
                'links' => $salesOrders->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $salesOrders->currentPage(),
                    'last_page' => $salesOrders->lastPage(),
                    'per_page' => $salesOrders->perPage(),
                    'total' => $salesOrders->total(),
                    'from' => $salesOrders->firstItem(),
                    'to' => $salesOrders->lastItem(),
                ],
            ],
            'filters' => $request->only(['search', 'status', 'customer_id', 'sort', 'direction']),
            'customers' => Customer::orderBy('customer_name')->get(['id', 'customer_name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'statuses' => Sales_Order::STATUSES,
        ]);
    }

    /**
     * Store a new sales order.
     */
    public function store(StoreSalesOrderRequest $request)
    {
        $validated = $request->validated();

        $salesOrder = DB::transaction(function () use ($validated) {
            // Auto-generate SO number: SO-YYYYMMDD-XXXX
            $today = now()->format('Ymd');
            $lastSo = Sales_Order::where('so_number', 'like', "SO-{$today}-%")
                ->orderByRaw("CAST(SUBSTRING(so_number, -4) AS UNSIGNED) DESC")
                ->value('so_number');

            if ($lastSo) {
                $lastNum = (int) substr($lastSo, -4);
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }

            $soNumber = "SO-{$today}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $salesOrder = Sales_Order::create([
                'so_number' => $soNumber,
                'customer_id' => $validated['customer_id'],
                'created_by' => Auth::id(),
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => Sales_Order::STATUS_DRAFT,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $total += $subtotal;

                Sales_Order_Item::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $salesOrder->update(['total_amount' => $total]);

            return $salesOrder;
        });

        // Notify all users about new sales order
        AppNotification::notifyAll(
            Auth::id(),
            'sales_order',
            Auth::user()->name . ' created Sales Order ' . $salesOrder->so_number,
            'sales_order',
            $salesOrder->id,
            $salesOrder->so_number,
            route('admin.sales-orders.index')
        );

        return redirect()->route('admin.sales-orders.index')
            ->with('success', 'Sales Order created successfully.');
    }

    /**
     * Show sales order details.
     */
    public function show(string $id)
    {
        $so = Sales_Order::with(['customer', 'creator', 'items.product'])->findOrFail($id);

        return response()->json([
            'salesOrder' => (new SalesOrderResource($so))->resolve(),
        ]);
    }

    /**
     * Update sales order status (workflow transitions).
     */
    public function update(Request $request, string $id)
    {
        $so = Sales_Order::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Sales_Order::STATUSES),
            'notes' => 'nullable|string|max:2000',
        ]);

        $newStatus = $request->input('status');

        // Validate transition
        if (!$so->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => "Cannot transition from '{$so->status}' to '{$newStatus}'.",
            ]);
        }

        // If transitioning to COMPLETED → deduct stock
        if ($newStatus === Sales_Order::STATUS_COMPLETED) {
            $this->completeSalesOrder($so);
        } else {
            $so->update([
                'status' => $newStatus,
                'notes' => $request->input('notes') ?? $so->notes,
            ]);
        }

        // Notify about status change
        AppNotification::notifyAll(
            Auth::id(),
            'sales_order',
            Auth::user()->name . ' updated ' . $so->so_number . ' to ' . ucfirst(str_replace('_', ' ', $newStatus)),
            'sales_order',
            $so->id,
            $so->so_number,
            route('admin.sales-orders.index')
        );

        return redirect()->route('admin.sales-orders.index')
            ->with('success', "Sales Order status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }

    /**
     * Complete a SO: deduct stock with DB transaction + row locking + ledger entries.
     * Prevents negative stock — aborts if any item has insufficient stock.
     */
    private function completeSalesOrder(Sales_Order $so): void
    {
        DB::transaction(function () use ($so) {
            $so->load('items');

            foreach ($so->items as $item) {
                // Lock product row for update (ACID + race condition prevention)
                $product = Products::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new \Exception("Product ID {$item->product_id} not found.");
                }

                $quantityBefore = $product->current_stock;
                $quantityChanged = $item->quantity;
                $quantityAfter = $quantityBefore - $quantityChanged;

                // PREVENT NEGATIVE STOCK
                if ($quantityAfter < 0) {
                    throw new \Exception(
                        "Insufficient stock for {$product->name} (SKU: {$product->sku}). " .
                        "Available: {$quantityBefore}, Requested: {$quantityChanged}."
                    );
                }

                // Deduct product stock
                $product->update(['current_stock' => $quantityAfter]);

                // Create stock ledger entry (OUT movement)
                Stock_Ledger::create([
                    'product_id' => $product->id,
                    'reference_type' => 'sale',
                    'reference_id' => $so->id,
                    'movement_type' => 'out',
                    'quantity' => $quantityChanged,
                    'balance_after' => $quantityAfter,
                    'created_by' => Auth::id(),
                ]);
            }

            $so->update(['status' => Sales_Order::STATUS_COMPLETED]);
        });

        // Dispatch email job AFTER successful transaction commit
        SendSalesOrderCompletedEmail::dispatch($so)
            ->onQueue('emails')
            ->afterCommit();
    }

    // ─── Export Implementation ────────────────────────────────────

    protected function getExportQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Sales_Order::with(['customer', 'creator'])->withCount('items');

        $this->applySearch($query, $request->input('search'), ['so_number'], ['customer' => 'customer_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $this->applySort($query, $request, ['so_number', 'order_date', 'delivery_date', 'total_amount', 'status', 'created_at']);

        return $query;
    }

    protected function getExportColumns(): array
    {
        return [
            ['header' => 'SO Number',      'key' => 'so_number',      'type' => 'String'],
            ['header' => 'Customer',        'type' => 'String', 'formatter' => fn ($row) => $row->customer?->customer_name ?? '—'],
            ['header' => 'Order Date',      'key' => 'order_date',     'type' => 'String'],
            ['header' => 'Delivery Date',   'key' => 'delivery_date',  'type' => 'String'],
            ['header' => 'Status',          'type' => 'String', 'formatter' => fn ($row) => ucfirst(str_replace('_', ' ', $row->status))],
            ['header' => 'Total Amount',    'key' => 'total_amount',   'type' => 'Number', 'style' => 'currency'],
            ['header' => 'Items',           'key' => 'items_count',    'type' => 'Number'],
            ['header' => 'Created By',      'type' => 'String', 'formatter' => fn ($row) => $row->creator?->name ?? '—'],
            ['header' => 'Created At',      'type' => 'String', 'formatter' => fn ($row) => $row->created_at?->format('Y-m-d H:i:s')],
        ];
    }

    protected function getExportFilename(): string { return 'sales-orders'; }
    protected function getReportView(): string { return 'reports.sales-orders'; }
    protected function getReportTitle(): string { return 'Sales Orders Report'; }

    protected function buildReportFilters(\Illuminate\Http\Request $request): array
    {
        $filters = [];
        if ($request->filled('search')) $filters[] = 'Search: "' . $request->input('search') . '"';
        if ($request->filled('status')) $filters[] = 'Status: ' . ucfirst(str_replace('_', ' ', $request->input('status')));
        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->input('customer_id'));
            $filters[] = 'Customer: ' . ($customer ? $customer->customer_name : '#' . $request->input('customer_id'));
        }
        return $filters;
    }
}
