<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for low stock item data.
 */
class LowStockReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => (int) $this->id,
            'sku'           => $this->sku,
            'name'          => $this->name,
            'current_stock' => (int) $this->current_stock,
            'unit_price'    => round((float) $this->unit_price, 2),
            'category_name' => $this->category_name,
        ];
    }
}
