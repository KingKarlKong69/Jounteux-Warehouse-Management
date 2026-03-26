<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'unit_price' => number_format($this->unit_price, 2),
            'unit_price_raw' => $this->unit_price,
            'current_stock' => $this->current_stock,
            'stock_status' => $this->getStockStatus(),
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }

    private function getStockStatus(): string
    {
        if($this->current_stock == 0){
            return 'out_of_stock';
        } elseif($this->current_stock <= 10){
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }
}
