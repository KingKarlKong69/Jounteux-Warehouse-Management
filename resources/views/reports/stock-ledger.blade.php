@extends('reports.layout')

@section('title', 'Stock Ledger Report')

@section('summary')
<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card green">
        <div class="label">Total IN</div>
        <div class="value">{{ number_format($summary['total_in']) }}</div>
    </div>
    <div class="summary-card red">
        <div class="label">Total OUT</div>
        <div class="value">{{ number_format($summary['total_out']) }}</div>
    </div>
    <div class="summary-card blue">
        <div class="label">Net Movement</div>
        <div class="value">{{ ($summary['net_movement'] >= 0 ? '+' : '') . number_format($summary['net_movement']) }}</div>
    </div>
    @if($summary['current_stock'] !== null)
    <div class="summary-card purple">
        <div class="label">Current Stock</div>
        <div class="value">{{ number_format($summary['current_stock']) }}</div>
    </div>
    @endif
</div>
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Type</th>
            <th>Reference</th>
            <th>Movement</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Balance After</th>
            <th>Processed By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $entry)
        <tr>
            <td>{{ $entry->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
            <td>{{ ($entry->product?->sku ?? '—') . ' — ' . ($entry->product?->name ?? 'Unknown') }}</td>
            <td>{{ ucfirst($entry->reference_type) }}</td>
            <td>
                @if($entry->reference_id)
                    @switch($entry->reference_type)
                        @case('purchase') PO-{{ str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT) }} @break
                        @case('sale') SO-{{ str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT) }} @break
                        @case('adjustment') ADJ-{{ str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT) }} @break
                        @default #{{ $entry->reference_id }}
                    @endswitch
                @else
                    —
                @endif
            </td>
            <td>
                <span class="badge {{ $entry->movement_type === 'in' ? 'badge-in' : 'badge-out' }}">
                    {{ strtoupper($entry->movement_type) }}
                </span>
            </td>
            <td class="text-right">{{ number_format($entry->quantity) }}</td>
            <td class="text-right">{{ number_format($entry->balance_after) }}</td>
            <td>{{ $entry->creator?->name ?? 'System' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center" style="padding: 24px; color: #9ca3af;">
                No ledger entries found for the selected filters.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
