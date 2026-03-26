<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Events\AuditLogEvent;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        event(new AuditLogEvent(
            user: $event->user,
            action: AuditAction::LOGOUT->value,
            auditableType: $event->user ? get_class($event->user) : null,
            auditableId: $event->user?->id,
            oldValues: null,
            newValues: ['guard' => $event->guard],
            ipAddress: request()->ip(),
            metadata: ['triggered_by' => 'system'],
            eventLabel: 'Logout',
        ));
    }
}
