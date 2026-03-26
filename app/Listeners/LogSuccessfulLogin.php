<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Events\AuditLogEvent;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        event(new AuditLogEvent(
            user: $event->user,
            action: AuditAction::LOGIN->value,
            auditableType: get_class($event->user),
            auditableId: $event->user->id,
            oldValues: null,
            newValues: ['guard' => $event->guard],
            ipAddress: request()->ip(),
            metadata: ['triggered_by' => 'system'],
            eventLabel: 'Login Successful',
        ));
    }
}
