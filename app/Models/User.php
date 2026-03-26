<?php

namespace App\Models;

use App\Enums\BlockReason;
use App\Enums\UserRole;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'password',
        'role',
        'failed_login_attempts',
        'is_blocked',
        'blocked_at',
        'block_reason',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'block_reason' => BlockReason::class,
        ];
    }


    // ─── Query Scopes ───────────────────────────────────────────

    /**
     * Filter by role.
     */
    public function scopeOfRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Filter between dates on created_at.
     */
    public function scopeDateBetween($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Quick date presets.
     */
    public function scopeDatePreset($query, string $preset)
    {
        return match ($preset) {
            'daily' => $query->whereDate('created_at', today()),
            'weekly' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'monthly' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'yearly' => $query->whereYear('created_at', now()->year),
            default => $query,
        };
    }

    // ─── Relationships ──────────────────────────────────────────

    public function purchaseOrders()
    {
        return $this->hasMany(Purchase_Order::class, 'created_by');
    }

    public function salesOrders()
    {
        return $this->hasMany(Sales_Order::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(Audit_Logs::class);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Get the URL for the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return Storage::url($this->profile_photo_path);
        }
        return null;
    }
}

