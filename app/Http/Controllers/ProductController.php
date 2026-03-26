<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Inertia\Inertia;
use App\Models\Products;
use App\Models\Category;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Stock_Ledger;
use App\Traits\HasIndexFilters;
use App\Traits\HasExport;


class ProductController extends Controller
{
    use HasIndexFilters, HasExport;

    /**
     * Display a listing of the resource.
     */
  public function index(Request $request)
{
    $query = Products::with('category');

    // Show trashed products if requested
    if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
        $query->onlyTrashed();
    }

    $this->applySearch($query, $request->input('search'), ['name', 'sku', 'description']);

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->filled('stock_threshold')) {
        $query->where('current_stock', '<=', $request->stock_threshold);
    }

    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    $products = $query->latest()->paginate(6)->withQueryString();

    return Inertia::render('Admin/Products/Index', [
        'products' => [
            'data' => ProductResource::collection($products->items())->toArray(request()),
            'links' => $products->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ],
        'filters' => $request->only(['search', 'stock_threshold', 'date_from', 'date_to', 'show_deleted', 'category_id']),
        'trashedCount' => Products::onlyTrashed()->count(),
        'categories' => Category::withCount('products')->orderBy('name')->get()->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'products_count' => $cat->products_count,
        ]),
    ]);
}

    /**
     * Search products (JSON API for PO modal, etc.)
     */
    public function search(Request $request)
    {
        $query = Products::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'sku', 'unit_price', 'current_stock'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'name';
        if (!in_array($sortDirection, ['asc', 'desc'])) $sortDirection = 'asc';

        $products = $query->orderBy($sortField, $sortDirection)
            ->limit(20)
            ->get(['id', 'sku', 'name', 'unit_price', 'current_stock', 'category_id']);

        return response()->json(['products' => $products]);
    }



    /**
     * Show the form for creating a new resource.
     */
    


    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::withCount('products')->orderBy('name')->get()->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'products_count' => $cat->products_count,
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // Auto-generate SKU: CATEGORYID-XXXX
        $categoryId = $validated['category_id'];

        // Find the highest existing SKU number for this category (including soft-deleted)
        $lastSku = Products::withTrashed()
            ->where('category_id', $categoryId)
            ->where('sku', 'like', $categoryId . '-%')
            ->orderByRaw("CAST(SUBSTRING(sku, LOCATE('-', sku) + 1) AS UNSIGNED) DESC")
            ->value('sku');

        if ($lastSku) {
            $lastNumber = (int) substr($lastSku, strrpos($lastSku, '-') + 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $validated['sku'] = $categoryId . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        DB::transaction(function () use ($validated) {
            $product = Products::create($validated);

            // If initial stock > 0, create a stock ledger adjustment entry
            if ($product->current_stock > 0) {
                Stock_Ledger::create([
                    'product_id' => $product->id,
                    'reference_type' => 'adjustment',
                    'reference_id' => null,
                    'movement_type' => 'in',
                    'quantity' => $product->current_stock,
                    'balance_after' => $product->current_stock,
                    'created_by' => Auth::id(),
                ]);
            }
        });

        // Return Inertia visit response to reload index properly
        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Products::with('category')->findOrFail($id);

        // Recent stock movements (last 20)
        $stockLedgers = Stock_Ledger::where('product_id', $id)
            ->with(['creator:id,name', 'product:id,sku,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Products/Show', [
            'product' => (new ProductResource($product))->resolve(),
            'stockLedgers' => \App\Http\Resources\StockLedgerResource::collection($stockLedgers)->toArray(request()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Products::with('category')->findOrFail($id);

        return Inertia::render('Admin/Products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Products::findOrFail($id);

        $validated = $request->validated();

        // SKU, category_id, and current_stock are immutable — never update them
        unset($validated['sku'], $validated['category_id'], $validated['current_stock']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id)
    {
        $product = Products::findOrFail($id);
        
        $product->delete(); // This performs a soft delete

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(string $id)
    {
        $product = Products::onlyTrashed()->findOrFail($id);
        
        $product->restore();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product restored successfully.');
    }

    /**
     * Permanently delete a product.
     */
    public function forceDelete(string $id)
    {
        $product = Products::onlyTrashed()->findOrFail($id);
        
        $product->forceDelete();

        return redirect()->route('admin.products.index', ['show_deleted' => 'true'])
            ->with('success', 'Product permanently deleted.');
    }

    // ─── Export Implementation ────────────────────────────────────

    protected function getExportQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Products::with('category');

        $this->applySearch($query, $request->input('search'), ['name', 'sku', 'description']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('stock_threshold')) {
            $query->where('current_stock', '<=', $request->stock_threshold);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->latest();
    }

    protected function getExportColumns(): array
    {
        return [
            ['header' => 'SKU',           'key' => 'sku',           'type' => 'String'],
            ['header' => 'Name',          'key' => 'name',          'type' => 'String'],
            ['header' => 'Category',      'key' => 'category.name', 'type' => 'String'],
            ['header' => 'Unit Price',    'key' => 'unit_price',    'type' => 'Number', 'style' => 'currency'],
            ['header' => 'Current Stock', 'key' => 'current_stock', 'type' => 'Number'],
            ['header' => 'Description',   'key' => 'description',   'type' => 'String'],
            ['header' => 'Created At',    'type' => 'String', 'formatter' => fn ($row) => $row->created_at?->format('Y-m-d H:i:s')],
        ];
    }

    protected function getExportFilename(): string { return 'products'; }
    protected function getReportView(): string { return 'reports.products'; }
    protected function getReportTitle(): string { return 'Products Report'; }

    protected function buildReportFilters(\Illuminate\Http\Request $request): array
    {
        $filters = [];
        if ($request->filled('search')) $filters[] = 'Search: "' . $request->input('search') . '"';
        if ($request->filled('category_id')) {
            $cat = Category::find($request->input('category_id'));
            $filters[] = 'Category: ' . ($cat ? $cat->name : '#' . $request->input('category_id'));
        }
        if ($request->filled('stock_threshold')) $filters[] = 'Stock ≤ ' . $request->input('stock_threshold');
        if ($request->filled('date_from')) $filters[] = 'From: ' . $request->input('date_from');
        if ($request->filled('date_to')) $filters[] = 'To: ' . $request->input('date_to');
        return $filters;
    }
}
