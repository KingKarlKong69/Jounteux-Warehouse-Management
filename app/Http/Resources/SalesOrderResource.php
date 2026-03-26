<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'so_number' => $this->so_number,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'customer_name' => $this->customer->customer_name,
            ]),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'order_date' => $this->order_date?->format('M d, Y'),
            'order_date_raw' => $this->order_date?->format('Y-m-d'),
            'delivery_date' => $this->delivery_date?->format('M d, Y'),
            'delivery_date_raw' => $this->delivery_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'total_amount' => number_format($this->total_amount, 2),
            'total_amount_raw' => $this->total_amount,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'current_stock' => $item->product->current_stock,
                ] : null,
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2),
                'unit_price_raw' => $item->unit_price,
                'subtotal' => number_format($item->subtotal, 2),
                'subtotal_raw' => $item->subtotal,
            ])),
            'items_count' => $this->whenCounted('items'),
            'allowed_transitions' => $this->resource->allowedTransitions(),
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'for_processing' => 'For Processing',
            'for_shipment' => 'For Shipment',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    private function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'yellow',
            'for_processing' => 'blue',
            'for_shipment' => 'purple',
            'completed' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
