<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class AuditLogEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?User $user;
    public string $action;
    public ?string $eventLabel;
    public ?string $auditableType;
    public ?int $auditableId;
    public ?array $oldValues;
    public ?array $newValues;
    public ?string $ipAddress;
    public ?array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ?User $user,
        string $action,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?array $metadata = null,
        ?string $eventLabel = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->eventLabel = $eventLabel;
        $this->auditableType = $auditableType;
        $this->auditableId = $auditableId;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        $this->ipAddress = $ipAddress;
        $this->metadata = $metadata;
    }
}
