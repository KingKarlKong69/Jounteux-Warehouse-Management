<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for purchase order analytics data.
 * Each record = one status-month combination.
 */
class PurchaseOrderReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'status'       => $this->status,
            'month'        => $this->month,
            'count'        => (int) $this->count,
            'total_amount' => round((float) $this->total_amount, 2),
        ];
    }

    /**
     * Build a structured response for the full PO analytics payload.
     */
    public static function buildResponse(
        $statusOverTime,
        $statusSummary,
        float $totalValue
    ): array {
        return [
            'status_over_time' => $statusOverTime->map(fn ($item) => [
                'status'       => $item->status,
                'month'        => $item->month,
                'count'        => (int) $item->count,
                'total_amount' => round((float) $item->total_amount, 2),
            ])->values()->toArray(),
            'status_summary'   => $statusSummary->map(fn ($item) => [
                'status'       => $item->status,
                'count'        => (int) $item->count,
                'total_amount' => round((float) $item->total_amount, 2),
            ])->values()->toArray(),
            'total_value'      => round($totalValue, 2),
        ];
    }
}
