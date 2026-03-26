<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLedgerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'product_sku'         => $this->product?->sku ?? '—',
            'product_name'        => $this->product?->name ?? 'Unknown Product',
            'product_label'       => $this->product ? ($this->product->sku . ' — ' . $this->product->name) : '—',
            'reference_type'      => $this->reference_type,
            'reference_type_label'=> $this->referenceTypeLabel(),
            'reference_id'        => $this->reference_id,
            'reference_label'     => $this->referenceLabel(),
            'reference_url'       => $this->referenceUrl(),
            'movement_type'       => $this->movement_type,
            'movement_label'      => strtoupper($this->movement_type),
            'quantity'            => $this->quantity,
            'balance_after'       => $this->balance_after,
            'created_by'          => $this->created_by,
            'created_by_name'     => $this->creator?->name ?? 'System',
            'created_at'          => $this->created_at?->toIso8601String(),
            'created_at_human'    => $this->created_at?->diffForHumans(),
            'created_at_formatted'=> $this->created_at?->format('M d, Y h:i A'),
            'created_at_date'     => $this->created_at?->format('Y-m-d'),
        ];
    }

    /**
     * Human-readable reference type.
     */
    protected function referenceTypeLabel(): string
    {
        return match ($this->reference_type) {
            'purchase'   => 'Purchase Order',
            'sale'       => 'Sales Order',
            'adjustment' => 'Adjustment',
            default      => ucfirst($this->reference_type ?? 'Unknown'),
        };
    }

    /**
     * Formatted reference identifier (e.g., PO-0001, SO-0005).
     */
    protected function referenceLabel(): string
    {
        if (!$this->reference_id) {
            return '—';
        }

        return match ($this->reference_type) {
            'purchase'   => 'PO-' . str_pad($this->reference_id, 4, '0', STR_PAD_LEFT),
            'sale'       => 'SO-' . str_pad($this->reference_id, 4, '0', STR_PAD_LEFT),
            'adjustment' => 'ADJ-' . str_pad($this->reference_id, 4, '0', STR_PAD_LEFT),
            default      => '#' . $this->reference_id,
        };
    }

    /**
     * URL to source transaction (for clickable links).
     */
    protected function referenceUrl(): ?string
    {
        if (!$this->reference_id) {
            return null;
        }

        return match ($this->reference_type) {
            'purchase'   => route('admin.purchase-orders.show', $this->reference_id),
            'sale'       => route('admin.sales-orders.show', $this->reference_id),
            default      => null,
        };
    }
}
