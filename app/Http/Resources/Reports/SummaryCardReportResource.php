<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for dashboard summary card data.
 */
class SummaryCardReportResource extends JsonResource
{
    /**
     * Disable wrapping so data is returned flat.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return collect($data)->map(function ($value) {
            if (is_numeric($value)) {
                return is_float($value + 0) ? round((float) $value, 2) : (int) $value;
            }
            return $value;
        })->toArray();
    }
}
