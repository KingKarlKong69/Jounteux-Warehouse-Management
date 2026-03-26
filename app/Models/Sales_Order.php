<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sales_Order extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'sales_orders';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_FOR_PROCESSING = 'for_processing';
    const STATUS_FOR_SHIPMENT = 'for_shipment';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_FOR_PROCESSING,
        self::STATUS_FOR_SHIPMENT,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
    ];

    // Allowed transitions: current_status => [allowed_next_statuses]
    const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_FOR_PROCESSING, self::STATUS_REJECTED],
        self::STATUS_FOR_PROCESSING => [self::STATUS_FOR_SHIPMENT, self::STATUS_REJECTED],
        self::STATUS_FOR_SHIPMENT => [self::STATUS_COMPLETED, self::STATUS_REJECTED],
        self::STATUS_COMPLETED => [],
        self::STATUS_REJECTED => [],
    ];

    protected $fillable = [
        'so_number',
        'customer_id',
        'created_by',
        'order_date',
        'delivery_date',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(Sales_Order_Item::class, 'sales_order_id');
    }

    /**
     * Check if a status transition is allowed.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Get allowed next statuses.
     */
    public function allowedTransitions(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [];
    }
}
