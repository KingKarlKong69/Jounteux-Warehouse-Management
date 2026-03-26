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
use App\Http\Resources\SalesOrderResource;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\HasIndexFilters;

class StaffSalesOrderController extends Controller
{
    use HasIndexFilters;

    /**
     * Display listing of the staff member's own sales orders.
     */
    public function index(Request $request)
    {
        $query = Sales_Order::with(['customer', 'creator'])
            ->withCount('items')
            ->where('created_by', Auth::id()); // Staff only sees own orders

        $this->applySearch($query, $request->input('search'), ['so_number'], ['customer' => 'customer_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $this->applySort($query, $request, ['so_number', 'order_date', 'delivery_date', 'total_amount', 'status', 'created_at']);

        $salesOrders = $query->paginate(10)->withQueryString();

        return Inertia::render('Staff/SalesOrders/Index', [
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
            'staffRestricted' => true, // Flag for Vue to restrict actions
        ]);
    }

    /**
     * Store a new sales order (same as admin).
     */
    public function store(StoreSalesOrderRequest $request)
    {
        $validated = $request->validated();

        $salesOrder = DB::transaction(function () use ($validated) {
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

        // Notify admins about new staff sales order
        AppNotification::notifyAdmins(
            Auth::id(),
            'sales_order',
            Auth::user()->name . ' created Sales Order ' . $salesOrder->so_number,
            'sales_order',
            $salesOrder->id,
            $salesOrder->so_number,
            route('admin.sales-orders.index')
        );

        return redirect()->route('staff.sales-orders.index')
            ->with('success', 'Sales Order created successfully.');
    }

    /**
     * Show sales order details (only own orders).
     */
    public function show(string $id)
    {
        $so = Sales_Order::with(['customer', 'creator', 'items.product'])
            ->where('created_by', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'salesOrder' => (new SalesOrderResource($so))->resolve(),
        ]);
    }

    /**
     * Update sales order status — staff can only reject.
     */
    public function update(Request $request, string $id)
    {
        $so = Sales_Order::where('created_by', Auth::id())->findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:rejected',
            'notes' => 'nullable|string|max:2000',
        ]);

        $newStatus = $request->input('status');

        if (!$so->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => "Cannot transition from '{$so->status}' to '{$newStatus}'.",
            ]);
        }

        $so->update([
            'status' => $newStatus,
            'notes' => $request->input('notes') ?? $so->notes,
        ]);

        // Notify admins about staff rejection
        AppNotification::notifyAdmins(
            Auth::id(),
            'sales_order',
            Auth::user()->name . ' rejected Sales Order ' . $so->so_number,
            'sales_order',
            $so->id,
            $so->so_number,
            route('admin.sales-orders.index')
        );

        return redirect()->route('staff.sales-orders.index')
            ->with('success', 'Sales Order has been rejected.');
    }
}
