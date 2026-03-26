<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit_Logs extends Model
{
    use HasFactory;

    // Immutable: disable updates
    public static function boot()
    {
        parent::boot();

        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    protected $table = 'audit_logs';

    // No updated_at needed for immutable logs
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'event_label',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'action' => AuditAction::class,
    ];

    // ───── Relationships ─────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // ───── Query Scopes ─────

    /**
     * Filter by action type(s).
     */
    public function scopeForAction($query, string|array $actions)
    {
        $actions = is_array($actions) ? $actions : [$actions];
        return $query->whereIn('action', $actions);
    }

    /**
     * Filter by date range.
     */
    public function scopeDateBetween($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }

    /**
     * Quick date filter presets.
     */
    public function scopeDatePreset($query, ?string $preset)
    {
        return match ($preset) {
            'daily' => $query->whereDate('created_at', today()),
            'weekly' => $query->where('created_at', '>=', now()->startOfWeek()),
            'monthly' => $query->where('created_at', '>=', now()->startOfMonth()),
            'yearly' => $query->where('created_at', '>=', now()->startOfYear()),
            default => $query,
        };
    }
}
