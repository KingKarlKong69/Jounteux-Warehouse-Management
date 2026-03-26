<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_Ledger extends Model
{
    use HasFactory;

    protected $table = 'stock_ledgers';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'reference_type',
        'reference_id',
        'movement_type',
        'quantity',
        'balance_after',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
