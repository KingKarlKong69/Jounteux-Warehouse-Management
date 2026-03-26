@extends('reports.layout')

@section('title', $title ?? 'Products Report')

@section('summary')
@if(isset($entries) && count($entries) > 0)
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Products</div>
        <div class="value">{{ number_format(count($entries)) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">Total Value</div>
        <div class="value">₱{{ number_format($entries->sum(fn($p) => $p->unit_price * $p->current_stock), 2) }}</div>
    </div>
    <div class="summary-card red">
        <div class="label">Low Stock (≤10)</div>
        <div class="value">{{ $entries->where('current_stock', '<=', 10)->count() }}</div>
    </div>
    <div class="summary-card amber">
        <div class="label">Out of Stock</div>
        <div class="value">{{ $entries->where('current_stock', '<=', 0)->count() }}</div>
    </div>
</div>
@endif
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th>SKU</th>
            <th>Product Name</th>
            <th>Category</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Current Stock</th>
            <th>Description</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $product)
            <tr>
                <td><strong>{{ $product->sku }}</strong></td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category?->name ?? '—' }}</td>
                <td class="text-right">₱{{ number_format($product->unit_price, 2) }}</td>
                <td class="text-right">
                    @if($product->current_stock <= 0)
                        <span class="badge badge-danger">{{ $product->current_stock }}</span>
                    @elseif($product->current_stock <= 10)
                        <span class="badge badge-warning">{{ $product->current_stock }}</span>
                    @else
                        {{ $product->current_stock }}
                    @endif
                </td>
                <td>{{ \Illuminate\Support\Str::limit($product->description, 50) }}</td>
                <td>{{ $product->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center" style="padding: 24px; color: #9ca3af;">No products found matching the applied filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
