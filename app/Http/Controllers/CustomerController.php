<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Sales_Order;
use App\Http\Resources\CustomerResource;
use App\Traits\HasIndexFilters;
use App\Traits\HasExport;

class CustomerController extends Controller
{
    use HasIndexFilters, HasExport;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::withCount('salesOrders');

        // Show trashed customers if requested
        if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
            $query->onlyTrashed();
        }

        $this->applySearch($query, $request->input('search'), ['customer_name', 'email', 'phone']);

        $this->applySort($query, $request, ['customer_name', 'email', 'created_at'], 'customer_name', 'asc');

        $customers = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => [
                'data' => CustomerResource::collection($customers->items())->toArray(request()),
                'links' => $customers->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ],
            ],
            'filters' => $request->only(['search', 'sort', 'direction', 'show_deleted']),
            'trashedCount' => Customer::onlyTrashed()->count(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        Customer::create($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validated();

        $customer->update($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);

        // Check if customer has any sales orders (including trashed)
        $soCount = Sales_Order::where('customer_id', $id)->count();
        if ($soCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete customer — {$soCount} sales order(s) are linked to this customer.",
            ]);
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore(string $id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);

        $customer->restore();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer restored successfully.');
    }

    /**
     * Permanently delete a customer.
     */
    public function forceDelete(string $id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);

        // Double-check no SOs linked
        $soCount = Sales_Order::where('customer_id', $id)->count();
        if ($soCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot permanently delete — {$soCount} sales order(s) are still linked.",
            ]);
        }

        $customer->forceDelete();

        return redirect()->route('admin.customers.index', ['show_deleted' => 'true'])
            ->with('success', 'Customer permanently deleted.');
    }

    // ─── Export Implementation ────────────────────────────────────

    protected function getExportQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Customer::withCount('salesOrders');

        $this->applySearch($query, $request->input('search'), ['customer_name', 'email', 'phone']);
        $this->applySort($query, $request, ['customer_name', 'email', 'created_at'], 'customer_name', 'asc');

        return $query;
    }

    protected function getExportColumns(): array
    {
        return [
            ['header' => 'Name',         'key' => 'customer_name', 'type' => 'String'],
            ['header' => 'Email',        'key' => 'email',         'type' => 'String'],
            ['header' => 'Phone',        'key' => 'phone',         'type' => 'String'],
            ['header' => 'Address',      'key' => 'address',       'type' => 'String'],
            ['header' => 'Sales Orders', 'key' => 'sales_orders_count', 'type' => 'Number'],
            ['header' => 'Created At',   'type' => 'String', 'formatter' => fn ($row) => $row->created_at?->format('Y-m-d H:i:s')],
        ];
    }

    protected function getExportFilename(): string { return 'customers'; }
    protected function getReportView(): string { return 'reports.customers'; }
    protected function getReportTitle(): string { return 'Customers Report'; }

    protected function buildReportFilters(\Illuminate\Http\Request $request): array
    {
        $filters = [];
        if ($request->filled('search')) $filters[] = 'Search: "' . $request->input('search') . '"';
        return $filters;
    }
}
