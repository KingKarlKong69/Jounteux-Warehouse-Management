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

class StaffCustomerController extends Controller
{
    use HasIndexFilters;
    public function index(Request $request)
    {
        $query = Customer::withCount('salesOrders');

        if ($request->filled('show_deleted') && $request->show_deleted === 'true') {
            $query->onlyTrashed();
        }

        $this->applySearch($query, $request->input('search'), ['customer_name', 'email', 'phone']);

        $this->applySort($query, $request, ['customer_name', 'email', 'created_at'], 'customer_name', 'asc');

        $customers = $query->paginate(10)->withQueryString();

        return Inertia::render('Staff/Customers/Index', [
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

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        Customer::create($validated);
        return redirect()->route('staff.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function update(UpdateCustomerRequest $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validated();
        $customer->update($validated);
        return redirect()->route('staff.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $soCount = Sales_Order::where('customer_id', $id)->count();
        if ($soCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete customer — {$soCount} sales order(s) are linked to this customer.",
            ]);
        }
        $customer->delete();
        return redirect()->route('staff.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function restore(string $id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();
        return redirect()->route('staff.customers.index')
            ->with('success', 'Customer restored successfully.');
    }

    public function forceDelete(string $id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $soCount = Sales_Order::where('customer_id', $id)->count();
        if ($soCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot permanently delete — {$soCount} sales order(s) are still linked.",
            ]);
        }
        $customer->forceDelete();
        return redirect()->route('staff.customers.index', ['show_deleted' => 'true'])
            ->with('success', 'Customer permanently deleted.');
    }
}
