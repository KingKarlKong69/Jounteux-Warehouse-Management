<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Purchase_Order;
use App\Http\Resources\SupplierResource;
use App\Traits\HasIndexFilters;
use App\Traits\HasExport;

class SupplierController extends Controller
{
    use HasIndexFilters, HasExport;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchaseOrders');

        // Show trashed suppliers if requested
        if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
            $query->onlyTrashed();
        }

        $this->applySearch($query, $request->input('search'), ['company_name', 'contact_person', 'email', 'phone']);

        $this->applySort($query, $request, ['company_name', 'email', 'created_at'], 'company_name', 'asc');

        $suppliers = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => [
                'data' => SupplierResource::collection($suppliers->items())->toArray(request()),
                'links' => $suppliers->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $suppliers->currentPage(),
                    'last_page' => $suppliers->lastPage(),
                    'per_page' => $suppliers->perPage(),
                    'total' => $suppliers->total(),
                    'from' => $suppliers->firstItem(),
                    'to' => $suppliers->lastItem(),
                ],
            ],
            'filters' => $request->only(['search', 'sort', 'direction', 'show_deleted']),
            'trashedCount' => Supplier::onlyTrashed()->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validated();

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        // Check if supplier has any purchase orders (including trashed)
        $poCount = Purchase_Order::where('supplier_id', $id)->count();
        if ($poCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete supplier — {$poCount} purchase order(s) are linked to this supplier.",
            ]);
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Restore a soft-deleted supplier.
     */
    public function restore(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        $supplier->restore();

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier restored successfully.');
    }

    /**
     * Permanently delete a supplier.
     */
    public function forceDelete(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        // Double-check no POs linked
        $poCount = Purchase_Order::where('supplier_id', $id)->count();
        if ($poCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot permanently delete — {$poCount} purchase order(s) are still linked.",
            ]);
        }

        $supplier->forceDelete();

        return redirect()->route('admin.suppliers.index', ['show_deleted' => 'true'])
            ->with('success', 'Supplier permanently deleted.');
    }

    // ─── Export Implementation ────────────────────────────────────

    protected function getExportQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Supplier::withCount('purchaseOrders');

        $this->applySearch($query, $request->input('search'), ['company_name', 'contact_person', 'email', 'phone']);
        $this->applySort($query, $request, ['company_name', 'email', 'created_at'], 'company_name', 'asc');

        return $query;
    }

    protected function getExportColumns(): array
    {
        return [
            ['header' => 'Company Name',    'key' => 'company_name',    'type' => 'String'],
            ['header' => 'Contact Person',  'key' => 'contact_person',  'type' => 'String'],
            ['header' => 'Email',           'key' => 'email',           'type' => 'String'],
            ['header' => 'Phone',           'key' => 'phone',           'type' => 'String'],
            ['header' => 'Address',         'key' => 'address',         'type' => 'String'],
            ['header' => 'Notes',           'key' => 'notes',           'type' => 'String'],
            ['header' => 'Purchase Orders', 'key' => 'purchase_orders_count', 'type' => 'Number'],
            ['header' => 'Created At',      'type' => 'String', 'formatter' => fn ($row) => $row->created_at?->format('Y-m-d H:i:s')],
        ];
    }

    protected function getExportFilename(): string { return 'suppliers'; }
    protected function getReportView(): string { return 'reports.suppliers'; }
    protected function getReportTitle(): string { return 'Suppliers Report'; }

    protected function buildReportFilters(\Illuminate\Http\Request $request): array
    {
        $filters = [];
        if ($request->filled('search')) $filters[] = 'Search: "' . $request->input('search') . '"';
        return $filters;
    }
}
