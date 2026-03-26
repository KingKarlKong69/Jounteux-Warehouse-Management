<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $changeSummary = $this->buildChangeSummary();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name ?? $this->user?->name ?? 'System',
            'user_role' => $this->user_role ?? $this->user?->role ?? 'unknown',
            'action' => $this->getRawOriginal('action'),
            'action_label' => $this->action?->label() ?? $this->getRawOriginal('action'),
            'action_color' => $this->action?->color() ?? 'gray',
            'event_label' => $this->event_label ?? $this->action?->label() ?? ucfirst($this->getRawOriginal('action')),
            'event_label_color' => $this->resolveEventLabelColor(),
            'auditable_type' => $this->auditable_type ? class_basename($this->auditable_type) : null,
            'auditable_type_label' => $this->formatAuditableType(),
            'auditable_id' => $this->auditable_id,
            'resource_label' => $this->formatResourceLabel(),
            'ip_address' => $this->ip_address,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'metadata' => $this->metadata,
            'change_summary' => $changeSummary,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'created_at_formatted' => $this->created_at?->format('M d, Y h:i A'),
        ];
    }

    /**
     * Format auditable_type to human-readable form.
     */
    protected function formatAuditableType(): ?string
    {
        if (!$this->auditable_type) return null;

        $basename = class_basename($this->auditable_type);
        $name = str_replace('_', ' ', $basename);
        // "Products" -> "Product", etc.
        if (str_ends_with($name, 's') && !str_ends_with($name, 'ss')) {
            $name = rtrim($name, 's');
        }
        return $name;
    }

    /**
     * Build a human-readable resource label (e.g., "Sales Order SO-20260219-0001").
     */
    protected function formatResourceLabel(): ?string
    {
        if (!$this->auditable_type) return null;

        $typeName = $this->formatAuditableType();
        $identifier = null;

        // Try to get identifier from metadata
        if ($this->metadata && isset($this->metadata['model_identifier'])) {
            $identifier = $this->metadata['model_identifier'];
        }

        if ($identifier) {
            return "{$typeName} {$identifier}";
        }

        return $this->auditable_id ? "{$typeName} #{$this->auditable_id}" : $typeName;
    }

    /**
     * Build a structured change summary from old/new values or metadata.
     */
    protected function buildChangeSummary(): array
    {
        // First try structured changes from metadata
        if ($this->metadata && isset($this->metadata['changes']) && is_array($this->metadata['changes'])) {
            return $this->metadata['changes'];
        }

        // For updates, build from old/new values
        $action = $this->getRawOriginal('action');
        if ($action !== 'updated' || !$this->new_values || !is_array($this->new_values)) {
            return [];
        }

        $excluded = [
            'updated_at', 'created_at', 'deleted_at', 'remember_token',
            'password', 'email_verified_at',
        ];

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            if (in_array($field, $excluded)) continue;

            $oldValue = is_array($this->old_values) ? ($this->old_values[$field] ?? null) : null;
            $label = ucfirst(str_replace('_', ' ', $field));

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    /**
     * Resolve a color for the event label badge.
     */
    protected function resolveEventLabelColor(): string
    {
        $label = strtolower($this->event_label ?? '');

        if (str_contains($label, 'completed')) return 'green';
        if (str_contains($label, 'approved')) return 'emerald';
        if (str_contains($label, 'created')) return 'blue';
        if (str_contains($label, 'processed') || str_contains($label, 'processing')) return 'sky';
        if (str_contains($label, 'shipment')) return 'indigo';
        if (str_contains($label, 'rejected')) return 'red';
        if (str_contains($label, 'cancelled')) return 'red';
        if (str_contains($label, 'archived') || str_contains($label, 'deleted')) return 'orange';
        if (str_contains($label, 'restored')) return 'emerald';
        if (str_contains($label, 'blocked')) return 'red';
        if (str_contains($label, 'unblocked')) return 'emerald';
        if (str_contains($label, 'updated')) return 'amber';
        if (str_contains($label, 'login successful')) return 'green';
        if (str_contains($label, 'login failed')) return 'yellow';
        if (str_contains($label, 'logout')) return 'gray';

        return $this->action?->color() ?? 'gray';
    }
}
