<?php

namespace App\Listeners;

use App\Events\AuditLogEvent;
use App\Models\Audit_Logs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AuditLogListener
{

    /**
     * Handle the event.
     */
    public function handle(AuditLogEvent $event): void
    {
        try {
            Audit_Logs::create([
                'user_id' => $event->user?->id,
                'user_name' => $event->user?->name ?? 'System',
                'user_role' => $event->user?->role ?? 'unknown',
                'action' => $event->action,
                'event_label' => $event->eventLabel,
                'auditable_type' => $event->auditableType,
                'auditable_id' => $event->auditableId,
                'old_values' => $event->oldValues,
                'new_values' => $event->newValues,
                'ip_address' => $event->ipAddress ?? request()->ip(),
                'metadata' => $event->metadata ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log audit event: ' . $e->getMessage(), [
                'action' => $event->action,
                'user_id' => $event->user?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
