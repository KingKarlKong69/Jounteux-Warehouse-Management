<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for supplier procurement summary data.
 */
class SupplierProcurementReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => (int) $this->id,
            'company_name'    => $this->company_name,
            'contact_person'  => $this->contact_person,
            'total_orders'    => (int) $this->total_orders,
            'total_amount'    => round((float) $this->total_amount, 2),
            'last_order_date' => $this->last_order_date,
        ];
    }
}
