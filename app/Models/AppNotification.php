<?php

namespace App\Models;

use App\Events\NotificationPushed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'message',
        'resource_type',
        'resource_id',
        'resource_label',
        'redirect_url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Accessors ─────────────────────────────────────────────

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // ─── Static Factory Methods ────────────────────────────────

    /**
     * Send a notification to specific users.
     * After DB insert, broadcasts via WebSocket to each recipient.
     */
    public static function notify(
        array  $recipientIds,
        ?int   $actorId,
        string $type,
        string $message,
        ?string $resourceType = null,
        ?int    $resourceId = null,
        ?string $resourceLabel = null,
        ?string $redirectUrl = null
    ): void {
        $now = now();
        $records = [];

        foreach ($recipientIds as $userId) {
            // Don't notify the actor themselves
            if ($userId === $actorId) continue;

            $records[] = [
                'user_id'        => $userId,
                'actor_id'       => $actorId,
                'type'           => $type,
                'message'        => $message,
                'resource_type'  => $resourceType,
                'resource_id'    => $resourceId,
                'resource_label' => $resourceLabel,
                'redirect_url'   => $redirectUrl,
                'is_read'        => false,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!empty($records)) {
            self::insert($records);

            // ── Broadcast to each recipient via WebSocket ──
            // Resolve the actor name once for the payload
            $actorName = $actorId ? (User::find($actorId)?->name ?? 'System') : 'System';
            $actorRole = $actorId ? (User::find($actorId)?->role ?? 'system') : 'system';

            foreach ($records as $record) {
                try {
                    event(new NotificationPushed($record['user_id'], [
                        'type'           => $record['type'],
                        'message'        => $record['message'],
                        'user_name'      => $actorName,
                        'user_role'      => $actorRole,
                        'resource_type'  => $record['resource_type'],
                        'resource_id'    => $record['resource_id'],
                        'resource_label' => $record['resource_label'],
                        'redirect_url'   => $record['redirect_url'],
                        'is_read'        => false,
                        'created_at'     => $record['created_at']->toIso8601String(),
                        'time_ago'       => 'just now',
                    ]));
                } catch (\Throwable $e) {
                    // Broadcasting is non-critical — log and continue
                    \Illuminate\Support\Facades\Log::warning(
                        "Failed to broadcast notification to user {$record['user_id']}: {$e->getMessage()}"
                    );
                }
            }
        }
    }

    /**
     * Send notification to all admins.
     */
    public static function notifyAdmins(
        ?int   $actorId,
        string $type,
        string $message,
        ?string $resourceType = null,
        ?int    $resourceId = null,
        ?string $resourceLabel = null,
        ?string $redirectUrl = null
    ): void {
        $adminIds = User::where('role', 'admin')
            ->where('is_blocked', false)
            ->pluck('id')
            ->toArray();

        self::notify($adminIds, $actorId, $type, $message, $resourceType, $resourceId, $resourceLabel, $redirectUrl);
    }

    /**
     * Send notification to all active users.
     */
    public static function notifyAll(
        ?int   $actorId,
        string $type,
        string $message,
        ?string $resourceType = null,
        ?int    $resourceId = null,
        ?string $resourceLabel = null,
        ?string $redirectUrl = null
    ): void {
        $userIds = User::where('is_blocked', false)
            ->pluck('id')
            ->toArray();

        self::notify($userIds, $actorId, $type, $message, $resourceType, $resourceId, $resourceLabel, $redirectUrl);
    }
}
