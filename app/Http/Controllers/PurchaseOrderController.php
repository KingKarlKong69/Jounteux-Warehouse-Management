<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePurchaseOrderRequest;
use Inertia\Inertia;
use App\Models\Purchase_Order;
use App\Models\Purchase_Order_Item;
use App\Models\Products;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Stock_Ledger;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\HasIndexFilters;
use App\Traits\HasExport;

class PurchaseOrderController extends Controller
{
    use HasIndexFilters, HasExport;

    /**
     * Display listing of purchase orders.
     */
    public function index(Request $request)
    {
        $query = Purchase_Order::with(['supplier', 'creator'])
            ->withCount('items');

        $this->applySearch($query, $request->input('search'), ['po_number'], ['supplier' => 'company_name']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->applySort($query, $request, ['po_number', 'order_date', 'total_amount', 'status', 'created_at']);

        $purchaseOrders = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/PurchaseOrders/Index', [
            'purchaseOrders' => [
                'data' => PurchaseOrderResource::collection($purchaseOrders->items())->toArray(request()),
                'links' => $purchaseOrders->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $purchaseOrders->currentPage(),
                    'last_page' => $purchaseOrders->lastPage(),
                    'per_page' => $purchaseOrders->perPage(),
                    'total' => $purchaseOrders->total(),
                    'from' => $purchaseOrders->firstItem(),
                    'to' => $purchaseOrders->lastItem(),
                ],
            ],
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'suppliers' => Supplier::orderBy('company_name')->get(['id', 'company_name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'statuses' => Purchase_Order::STATUSES,
        ]);
    }

    /**
     * Store a new purchase order.
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        $validated = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($validated) {
            // Auto-generate PO number: PO-YYYYMMDD-XXXX
            $today = now()->format('Ymd');
            $lastPo = Purchase_Order::where('po_number', 'like', "PO-{$today}-%")
                ->orderByRaw("CAST(SUBSTRING(po_number, -4) AS UNSIGNED) DESC")
                ->value('po_number');

            if ($lastPo) {
                $lastNum = (int) substr($lastPo, -4);
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }

            $poNumber = "PO-{$today}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $purchaseOrder = Purchase_Order::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'created_by' => Auth::id(),
                'order_date' => $validated['order_date'],
                'status' => Purchase_Order::STATUS_PENDING,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $total += $subtotal;

                Purchase_Order_Item::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $purchaseOrder->update(['total_amount' => $total]);

            return $purchaseOrder;
        });

        // Notify all users about new purchase order
        AppNotification::notifyAll(
            Auth::id(),
            'purchase_order',
            Auth::user()->name . ' created Purchase Order ' . $purchaseOrder->po_number,
            'purchase_order',
            $purchaseOrder->id,
            $purchaseOrder->po_number,
            route('admin.purchase-orders.index')
        );

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'Purchase Order created successfully.');
    }

    /**
     * Show purchase order details.
     */
    public function show(string $id)
    {
        $po = Purchase_Order::with(['supplier', 'creator', 'items.product'])->findOrFail($id);

        return response()->json([
            'purchaseOrder' => (new PurchaseOrderResource($po))->resolve(),
        ]);
    }

    /**
     * Update purchase order status (workflow transitions).
     */
    public function update(Request $request, string $id)
    {
        $po = Purchase_Order::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Purchase_Order::STATUSES),
            'notes' => 'nullable|string|max:2000',
        ]);

        $newStatus = $request->input('status');

        // Validate transition
        if (!$po->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => "Cannot transition from '{$po->status}' to '{$newStatus}'.",
            ]);
        }

        // If transitioning to COMPLETED → process stock
        if ($newStatus === Purchase_Order::STATUS_COMPLETED) {
            $this->completePurchaseOrder($po);
        } else {
            $po->update([
                'status' => $newStatus,
                'notes' => $request->input('notes') ?? $po->notes,
            ]);
        }

        // Notify about PO status change
        AppNotification::notifyAll(
            Auth::id(),
            'purchase_order',
            Auth::user()->name . ' updated ' . $po->po_number . ' to ' . ucfirst(str_replace('_', ' ', $newStatus)),
            'purchase_order',
            $po->id,
            $po->po_number,
            route('admin.purchase-orders.index')
        );

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', "Purchase Order status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }

    /**
     * Complete a PO: update stock with DB transaction + row locking + ledger entries.
     */
    private function completePurchaseOrder(Purchase_Order $po): void
    {
        DB::transaction(function () use ($po) {
            $po->load('items');

            foreach ($po->items as $item) {
                // Lock product row for update
                $product = Products::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new \Exception("Product ID {$item->product_id} not found.");
                }

                $quantityBefore = $product->current_stock;
                $quantityChanged = $item->quantity;
                $quantityAfter = $quantityBefore + $quantityChanged;

                // Update product stock
                $product->update(['current_stock' => $quantityAfter]);

                // Create stock ledger entry
                Stock_Ledger::create([
                    'product_id' => $product->id,
                    'reference_type' => 'purchase',
                    'reference_id' => $po->id,
                    'movement_type' => 'in',
                    'quantity' => $quantityChanged,
                    'balance_after' => $quantityAfter,
                    'created_by' => Auth::id(),
                ]);
            }

            $po->update(['status' => Purchase_Order::STATUS_COMPLETED]);
        });
    }

    // ─── Export Implementation ────────────────────────────────────

    protected function getExportQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Purchase_Order::with(['supplier', 'creator'])->withCount('items');

        $this->applySearch($query, $request->input('search'), ['po_number'], ['supplier' => 'company_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->applySort($query, $request, ['po_number', 'order_date', 'total_amount', 'status', 'created_at']);

        return $query;
    }

    protected function getExportColumns(): array
    {
        return [
            ['header' => 'PO Number',    'key' => 'po_number',    'type' => 'String'],
            ['header' => 'Supplier',      'type' => 'String', 'formatter' => fn ($row) => $row->supplier?->company_name ?? '—'],
            ['header' => 'Order Date',    'key' => 'order_date',   'type' => 'String'],
            ['header' => 'Status',        'type' => 'String', 'formatter' => fn ($row) => ucfirst(str_replace('_', ' ', $row->status))],
            ['header' => 'Total Amount',  'key' => 'total_amount', 'type' => 'Number', 'style' => 'currency'],
            ['header' => 'Items',         'key' => 'items_count',  'type' => 'Number'],
            ['header' => 'Created By',    'type' => 'String', 'formatter' => fn ($row) => $row->creator?->name ?? '—'],
            ['header' => 'Created At',    'type' => 'String', 'formatter' => fn ($row) => $row->created_at?->format('Y-m-d H:i:s')],
        ];
    }

    protected function getExportFilename(): string { return 'purchase-orders'; }
    protected function getReportView(): string { return 'reports.purchase-orders'; }
    protected function getReportTitle(): string { return 'Purchase Orders Report'; }

    protected function buildReportFilters(\Illuminate\Http\Request $request): array
    {
        $filters = [];
        if ($request->filled('search')) $filters[] = 'Search: "' . $request->input('search') . '"';
        if ($request->filled('status')) $filters[] = 'Status: ' . ucfirst(str_replace('_', ' ', $request->input('status')));
        return $filters;
    }
}
