<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Inertia\Inertia;
use App\Models\Products;
use App\Models\Category;
use App\Models\Stock_Ledger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasIndexFilters;

class StaffProductController extends Controller
{
    use HasIndexFilters;

    /**
     * Display a listing of products (same as admin).
     */
    public function index(Request $request)
    {
        $query = Products::with('category');

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

        return Inertia::render('Staff/Products/Index', [
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
     * Search products (JSON API).
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Products::with('category')->findOrFail($id);

        $stockLedgers = Stock_Ledger::where('product_id', $id)
            ->with(['creator:id,name', 'product:id,sku,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('Staff/Products/Show', [
            'product' => (new ProductResource($product))->resolve(),
            'stockLedgers' => \App\Http\Resources\StockLedgerResource::collection($stockLedgers)->toArray(request()),
        ]);
    }

    /**
     * Show form for creating a new product.
     */
    public function create()
    {
        return Inertia::render('Staff/Products/Create', [
            'categories' => Category::withCount('products')->orderBy('name')->get()->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'products_count' => $cat->products_count,
            ]),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $categoryId = $validated['category_id'];

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

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        DB::transaction(function () use ($validated) {
            $product = Products::create($validated);

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

        return redirect()->route('staff.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show form for editing the specified product.
     */
    public function edit(string $id)
    {
        $product = Products::with('category')->findOrFail($id);

        return Inertia::render('Staff/Products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Products::findOrFail($id);

        $validated = $request->validated();

        unset($validated['sku'], $validated['category_id'], $validated['current_stock']);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        $product->update($validated);

        return redirect()->route('staff.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Soft delete the specified product.
     */
    public function destroy(string $id)
    {
        $product = Products::findOrFail($id);
        $product->delete();

        return redirect()->route('staff.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(string $id)
    {
        $product = Products::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('staff.products.index')
            ->with('success', 'Product restored successfully.');
    }

    /**
     * Permanently delete a product.
     */
    public function forceDelete(string $id)
    {
        $product = Products::onlyTrashed()->findOrFail($id);
        $product->forceDelete();

        return redirect()->route('staff.products.index', ['show_deleted' => 'true'])
            ->with('success', 'Product permanently deleted.');
    }
}
