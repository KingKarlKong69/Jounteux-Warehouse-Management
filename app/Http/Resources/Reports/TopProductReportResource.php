<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for top-selling product report data.
 */
class TopProductReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id'     => (int) $this->product_id,
            'product_name'   => $this->product_name,
            'total_quantity' => (int) $this->total_quantity,
            'total_revenue'  => round((float) $this->total_revenue, 2),
        ];
    }
}
