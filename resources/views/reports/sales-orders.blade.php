@extends('reports.layout')

@section('title', $title ?? 'Sales Orders Report')

@section('summary')
@if(isset($entries) && count($entries) > 0)
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Orders</div>
        <div class="value">{{ number_format(count($entries)) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">Total Revenue</div>
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
            <th>SO Number</th>
            <th>Customer</th>
            <th>Order Date</th>
            <th>Delivery Date</th>
            <th>Status</th>
            <th class="text-right">Total Amount</th>
            <th class="text-center">Items</th>
            <th>Created By</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $so)
            <tr>
                <td><strong>{{ $so->so_number }}</strong></td>
                <td>{{ $so->customer?->customer_name ?? '—' }}</td>
                <td>{{ $so->order_date }}</td>
                <td>{{ $so->delivery_date ?? '—' }}</td>
                <td>
                    @php $statusClass = 'badge-' . $so->status; @endphp
                    <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $so->status)) }}</span>
                </td>
                <td class="text-right">₱{{ number_format($so->total_amount, 2) }}</td>
                <td class="text-center">{{ $so->items_count }}</td>
                <td>{{ $so->creator?->name ?? '—' }}</td>
                <td>{{ $so->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center" style="padding: 24px; color: #9ca3af;">No sales orders found matching the applied filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
