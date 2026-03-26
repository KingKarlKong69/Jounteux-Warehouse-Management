<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Customer extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'customer_name',
        'email',
        'phone',
        'address',
    ];

    public function salesOrders()
    {
        return $this->hasMany(Sales_Order::class);
    }
}
