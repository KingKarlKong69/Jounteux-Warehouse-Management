<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for daily sales aggregation data.
 * Transforms raw DB aggregates into a clean API response.
 */
class DailySalesReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'date'         => $this->date,
            'total_amount' => round((float) $this->total_amount, 2),
            'order_count'  => (int) $this->order_count,
        ];
    }
}
