@extends('reports.layout')

@section('title', $title ?? 'Customers Report')

@section('summary')
@if(isset($entries) && count($entries) > 0)
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Customers</div>
        <div class="value">{{ number_format(count($entries)) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">With Orders</div>
        <div class="value">{{ $entries->where('sales_orders_count', '>', 0)->count() }}</div>
    </div>
    <div class="summary-card purple">
        <div class="label">Total Orders</div>
        <div class="value">{{ number_format($entries->sum('sales_orders_count')) }}</div>
    </div>
</div>
@endif
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th class="text-center">Sales Orders</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $customer)
            <tr>
                <td><strong>{{ $customer->customer_name }}</strong></td>
                <td>{{ $customer->email ?? '—' }}</td>
                <td>{{ $customer->phone ?? '—' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($customer->address, 40) }}</td>
                <td class="text-center">{{ $customer->sales_orders_count ?? 0 }}</td>
                <td>{{ $customer->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center" style="padding: 24px; color: #9ca3af;">No customers found matching the applied filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
