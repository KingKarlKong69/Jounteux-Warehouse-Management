<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'unit_price',
        'current_stock',
        'image',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'current_stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(Purchase_Order_Item::class);
    }

    public function salesOrderItems()
    {
        return $this->hasMany(Sales_Order_Item::class);
    }

    public function stockLedgers()
    {
        return $this->hasMany(Stock_Ledger::class);
    }
}
