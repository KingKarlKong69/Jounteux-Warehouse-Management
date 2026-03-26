@extends('reports.layout')

@section('title', $title ?? 'Purchase Orders Report')

@section('summary')
@if(isset($entries) && count($entries) > 0)
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Orders</div>
        <div class="value">{{ number_format(count($entries)) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">Total Procurement</div>
        <div class="value">₱{{ number_format($entries->sum('total_amount'), 2) }}</div>
    </div>
    <div class="summary-card purple">
        <div class="label">Completed</div>
        <div class="value">{{ $entries->where('status', 'completed')->count() }}</div>
    </div>
    <div class="summary-card amber">
        <div class="label">Pending</div>
        <div class="value">{{ $entries->where('status', 'pending')->count() }}</div>
    </div>
</div>
@endif
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th>PO Number</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Status</th>
            <th class="text-right">Total Amount</th>
            <th class="text-center">Items</th>
            <th>Created By</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $po)
            <tr>
                <td><strong>{{ $po->po_number }}</strong></td>
                <td>{{ $po->supplier?->company_name ?? '—' }}</td>
                <td>{{ $po->order_date }}</td>
                <td>
                    @php $statusClass = 'badge-' . $po->status; @endphp
                    <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
                </td>
                <td class="text-right">₱{{ number_format($po->total_amount, 2) }}</td>
                <td class="text-center">{{ $po->items_count }}</td>
                <td>{{ $po->creator?->name ?? '—' }}</td>
                <td>{{ $po->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center" style="padding: 24px; color: #9ca3af;">No purchase orders found matching the applied filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
