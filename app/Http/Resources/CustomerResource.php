<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_formatted' => $this->formatPhone($this->phone),
            'address' => $this->address,
            'sales_orders_count' => $this->whenCounted('salesOrders'),
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
