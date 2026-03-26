<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ─────────────────────────────────────────────────────────────
 * NotificationPushed — Real-Time Notification Broadcast Event
 * ─────────────────────────────────────────────────────────────
 *
 * Fired immediately after an AppNotification is persisted to
 * the database. Broadcasts via Reverb (WebSocket) to the
 * recipient's private channel so the frontend can update the
 * notification bell in real-time without polling.
 *
 * Uses ShouldBroadcastNow to bypass the queue — notifications
 * must be delivered instantly, not batched.
 *
 * Channel: private-notifications.{userId}
 * Event name (frontend): NotificationPushed
 *
 * @see \App\Models\AppNotification
 */
class NotificationPushed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int    $userId       Recipient user ID (channel target)
     * @param array  $notification Serialized notification payload
     */
    public function __construct(
        public int   $userId,
        public array $notification,
    ) {}

    /**
     * Get the channel the event should broadcast on.
     * Private channel ensures only the authenticated owner can listen.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("notifications.{$this->userId}");
    }

    /**
     * The event name the frontend Echo client listens for.
     */
    public function broadcastAs(): string
    {
        return 'NotificationPushed';
    }

    /**
     * Data payload sent to the frontend.
     * Matches the shape expected by useAppNotifications composable.
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => $this->notification,
        ];
    }
}
