<?php

namespace App\Traits;

use App\Events\AuditLogEvent;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Fields to exclude from change tracking (noisy / sensitive fields).
     */
    protected static function auditExcludedFields(): array
    {
        return [
            'updated_at', 'created_at', 'deleted_at', 'remember_token',
            'password', 'email_verified_at',
        ];
    }

    /**
     * Human-readable field labels for change summaries.
     */
    protected static function auditFieldLabels(): array
    {
        return [];
    }

    /**
     * Get the human-readable model name (e.g., "Sales Order", "Purchase Order").
     */
    protected static function auditModelName(): string
    {
        $class = class_basename(static::class);
        // Convert class names like Sales_Order, Purchase_Order, Products, etc.
        $name = str_replace('_', ' ', $class);
        // Handle plurals: "Products" -> "Product"
        if (str_ends_with($name, 's') && !str_ends_with($name, 'ss')) {
            $name = rtrim($name, 's');
        }
        return $name;
    }

    /**
     * Generate a human-readable identifier for the model instance.
     */
    public function auditIdentifier(): string
    {
        // Check for common identifier fields
        if (property_exists($this, 'attributes') || method_exists($this, 'getAttribute')) {
            foreach (['so_number', 'po_number', 'sku', 'name', 'company_name', 'customer_name', 'email'] as $field) {
                $val = $this->getAttribute($field);
                if ($val) return $val;
            }
        }
        return '#' . $this->getKey();
    }

    /**
     * Build event label and metadata for a created event.
     */
    protected static function buildCreatedAuditContext($model): array
    {
        $modelName = static::auditModelName();
        $identifier = $model->auditIdentifier();

        return [
            'event_label' => "{$modelName} Created",
            'metadata' => [
                'triggered_by' => 'user',
                'model_identifier' => $identifier,
            ],
        ];
    }

    /**
     * Build event label and metadata for an updated event.
     */
    protected static function buildUpdatedAuditContext($model): array
    {
        $modelName = static::auditModelName();
        $identifier = $model->auditIdentifier();
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        $excluded = static::auditExcludedFields();
        $labels = static::auditFieldLabels();

        // Filter out excluded fields
        $meaningfulChanges = array_diff_key($changes, array_flip($excluded));

        // Build structured change summary
        $changeSummary = [];
        foreach ($meaningfulChanges as $field => $newValue) {
            $oldValue = $original[$field] ?? null;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $changeSummary[] = [
                'field' => $field,
                'label' => $label,
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        // Detect status transitions (common in orders)
        $eventLabel = "{$modelName} Updated";
        $metadata = [
            'triggered_by' => 'user',
            'model_identifier' => $identifier,
            'changes' => $changeSummary,
        ];

        if (isset($changes['status'])) {
            $oldStatus = $original['status'] ?? null;
            $newStatus = $changes['status'];
            $eventLabel = static::resolveStatusEventLabel($modelName, $oldStatus, $newStatus);
            $metadata['previous_status'] = $oldStatus;
            $metadata['new_status'] = $newStatus;
            $metadata['status_transition'] = true;
        }

        // Build a human-readable field summary for non-status updates
        if (!isset($changes['status']) && count($changeSummary) > 0) {
            $fieldNames = array_map(fn($c) => $c['label'], $changeSummary);
            $eventLabel = "{$modelName} Updated (" . implode(', ', array_slice($fieldNames, 0, 3)) . (count($fieldNames) > 3 ? '...' : '') . ")";
        }

        return [
            'event_label' => $eventLabel,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build event label and metadata for a deleted event.
     */
    protected static function buildDeletedAuditContext($model): array
    {
        $modelName = static::auditModelName();
        $identifier = $model->auditIdentifier();

        // Determine if soft delete or force delete
        $isSoftDelete = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class));
        $action = $isSoftDelete ? 'Archived' : 'Deleted';

        return [
            'event_label' => "{$modelName} {$action}",
            'metadata' => [
                'triggered_by' => 'user',
                'model_identifier' => $identifier,
                'deletion_type' => $isSoftDelete ? 'soft_delete' : 'permanent',
            ],
        ];
    }

    /**
     * Resolve a human-readable event label from a status transition.
     */
    protected static function resolveStatusEventLabel(string $modelName, ?string $oldStatus, string $newStatus): string
    {
        // Sales Order status labels
        $salesOrderLabels = [
            'draft' => 'Sales Order Created as Draft',
            'for_processing' => 'Sales Order Processed',
            'for_shipment' => 'Marked for Shipment',
            'completed' => 'Sales Order Completed',
            'rejected' => 'Sales Order Rejected',
        ];

        // Purchase Order status labels
        $purchaseOrderLabels = [
            'pending' => 'Purchase Order Pending',
            'approved' => 'Purchase Order Approved',
            'processing' => 'Purchase Order Processing',
            'for_shipment' => 'Marked for Shipment',
            'completed' => 'Purchase Order Completed',
            'rejected' => 'Purchase Order Rejected',
            'cancelled' => 'Purchase Order Cancelled',
        ];

        if (str_contains($modelName, 'Sales Order') || str_contains($modelName, 'Sales_Order')) {
            return $salesOrderLabels[$newStatus] ?? "Sales Order Status Changed to " . ucfirst(str_replace('_', ' ', $newStatus));
        }

        if (str_contains($modelName, 'Purchase Order') || str_contains($modelName, 'Purchase_Order')) {
            return $purchaseOrderLabels[$newStatus] ?? "Purchase Order Status Changed to " . ucfirst(str_replace('_', ' ', $newStatus));
        }

        return "{$modelName} Status Changed to " . ucfirst(str_replace('_', ' ', $newStatus));
    }

    public static function bootAuditable()
    {
        static::created(function ($model) {
            if (!Auth::check()) return;

            $context = static::buildCreatedAuditContext($model);

            event(new AuditLogEvent(
                user: Auth::user(),
                action: 'created',
                auditableType: get_class($model),
                auditableId: $model->id,
                oldValues: null,
                newValues: $model->getAttributes(),
                ipAddress: request()->ip(),
                metadata: $context['metadata'],
                eventLabel: $context['event_label'],
            ));
        });

        static::updated(function ($model) {
            if (!Auth::check()) return;

            $context = static::buildUpdatedAuditContext($model);

            event(new AuditLogEvent(
                user: Auth::user(),
                action: 'updated',
                auditableType: get_class($model),
                auditableId: $model->id,
                oldValues: $model->getOriginal(),
                newValues: $model->getChanges(),
                ipAddress: request()->ip(),
                metadata: $context['metadata'],
                eventLabel: $context['event_label'],
            ));
        });

        static::deleted(function ($model) {
            if (!Auth::check()) return;

            $isSoftDelete = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class));
            $action = $isSoftDelete ? 'archived' : 'deleted';
            $context = static::buildDeletedAuditContext($model);

            event(new AuditLogEvent(
                user: Auth::user(),
                action: $action,
                auditableType: get_class($model),
                auditableId: $model->id,
                oldValues: $model->getAttributes(),
                newValues: null,
                ipAddress: request()->ip(),
                metadata: $context['metadata'],
                eventLabel: $context['event_label'],
            ));
        });

        // Handle restored events for soft-deletable models
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                if (!Auth::check()) return;

                $modelName = static::auditModelName();
                $identifier = $model->auditIdentifier();

                event(new AuditLogEvent(
                    user: Auth::user(),
                    action: 'restored',
                    auditableType: get_class($model),
                    auditableId: $model->id,
                    oldValues: null,
                    newValues: $model->getAttributes(),
                    ipAddress: request()->ip(),
                    metadata: [
                        'triggered_by' => 'user',
                        'model_identifier' => $identifier,
                    ],
                    eventLabel: "{$modelName} Restored",
                ));
            });
        }
    }
}