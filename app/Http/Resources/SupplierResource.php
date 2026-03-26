<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_formatted' => $this->formatPhone($this->phone),
            'address' => $this->address,
            'notes' => $this->notes,
            'purchase_orders_count' => $this->whenCounted('purchaseOrders'),
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
            'deleted_at' => $this->deleted_at?->format('M d, Y'),
        ];
    }

    /**
     * Format phone as XXX XXX XXXX for display.
     */
    private function formatPhone(?string $phone): ?string
    {
        if (!$phone || strlen($phone) !== 10) return $phone;
        return substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 4);
    }
}
