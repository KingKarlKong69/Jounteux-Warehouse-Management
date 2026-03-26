<?php

namespace App\Http\Requests;

use App\Models\Products;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Configure the validator instance — add stock-level check per item.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                $productId = $item['product_id'] ?? null;
                $requestedQty = (int) ($item['quantity'] ?? 0);

                if (! $productId || $requestedQty < 1) {
                    continue;
                }

                $product = Products::find($productId);
                if (! $product) {
                    continue; // exists rule will catch this
                }

                if ($requestedQty > $product->current_stock) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Quantity ({$requestedQty}) exceeds available stock ({$product->current_stock}) for {$product->name}."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'order_date.required' => 'Order date is required.',
            'delivery_date.after_or_equal' => 'Delivery date must be on or after the order date.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'Please select a product for each item.',
            'items.*.product_id.exists' => 'Selected product does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
            'items.*.unit_price.min' => 'Unit price must be greater than 0.',
        ];
    }
}
