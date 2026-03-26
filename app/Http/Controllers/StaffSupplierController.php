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

class StaffSupplierController extends Controller
{
    use HasIndexFilters;
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchaseOrders');

        if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
            $query->onlyTrashed();
        }

        $this->applySearch($query, $request->input('search'), ['company_name', 'contact_person', 'email', 'phone']);

        $this->applySort($query, $request, ['company_name', 'email', 'created_at'], 'company_name', 'asc');

        $suppliers = $query->paginate(10)->withQueryString();

        return Inertia::render('Staff/Suppliers/Index', [
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

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        Supplier::create($validated);
        return redirect()->route('staff.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function update(UpdateSupplierRequest $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $validated = $request->validated();
        $supplier->update($validated);
        return redirect()->route('staff.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $poCount = Purchase_Order::where('supplier_id', $id)->count();
        if ($poCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete supplier — {$poCount} purchase order(s) are linked to this supplier.",
            ]);
        }
        $supplier->delete();
        return redirect()->route('staff.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    public function restore(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();
        return redirect()->route('staff.suppliers.index')
            ->with('success', 'Supplier restored successfully.');
    }

    public function forceDelete(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $poCount = Purchase_Order::where('supplier_id', $id)->count();
        if ($poCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot permanently delete — {$poCount} purchase order(s) are still linked.",
            ]);
        }
        $supplier->forceDelete();
        return redirect()->route('staff.suppliers.index', ['show_deleted' => 'true'])
            ->with('success', 'Supplier permanently deleted.');
    }
}
