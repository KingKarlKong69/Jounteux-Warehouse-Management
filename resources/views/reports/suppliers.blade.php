@extends('reports.layout')

@section('title', $title ?? 'Suppliers Report')

@section('summary')
@if(isset($entries) && count($entries) > 0)
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Suppliers</div>
        <div class="value">{{ number_format(count($entries)) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">With Orders</div>
        <div class="value">{{ $entries->where('purchase_orders_count', '>', 0)->count() }}</div>
    </div>
    <div class="summary-card purple">
        <div class="label">Total POs</div>
        <div class="value">{{ number_format($entries->sum('purchase_orders_count')) }}</div>
    </div>
</div>
@endif
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th>Company Name</th>
            <th>Contact Person</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th class="text-center">Purchase Orders</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $supplier)
            <tr>
                <td><strong>{{ $supplier->company_name }}</strong></td>
                <td>{{ $supplier->contact_person ?? '—' }}</td>
                <td>{{ $supplier->email ?? '—' }}</td>
                <td>{{ $supplier->phone ?? '—' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($supplier->address, 40) }}</td>
                <td class="text-center">{{ $supplier->purchase_orders_count ?? 0 }}</td>
                <td>{{ $supplier->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center" style="padding: 24px; color: #9ca3af;">No suppliers found matching the applied filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
