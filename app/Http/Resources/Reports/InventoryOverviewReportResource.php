<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for inventory overview timeline data.
 * Each record = one category-month combination.
 */
class InventoryOverviewReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'category'      => $this->category,
            'month'         => $this->month,
            'product_count' => (int) $this->product_count,
        ];
    }
}
