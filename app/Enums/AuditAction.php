<?php

namespace App\Enums;

enum AuditAction: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case CREATED = 'created';
    case UPDATED = 'updated';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
    case RESTORED = 'restored';
    case BLOCKED = 'blocked';
    case FAILED_LOGIN = 'failed_login';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOGIN => 'Login',
            self::LOGOUT => 'Logout',
            self::CREATED => 'Create',
            self::UPDATED => 'Update',
            self::ARCHIVED => 'Archive',
            self::DELETED => 'Delete',
            self::RESTORED => 'Restored',
            self::BLOCKED => 'Blocked',
            self::FAILED_LOGIN => 'Failed Login',
        };
    }

    /**
     * Badge color class for the frontend.
     */
    public function color(): string
    {
        return match ($this) {
            self::LOGIN => 'green',
            self::LOGOUT => 'gray',
            self::CREATED => 'blue',
            self::UPDATED => 'amber',
            self::ARCHIVED => 'orange',
            self::DELETED => 'red',
            self::RESTORED => 'emerald',
            self::BLOCKED => 'red',
            self::FAILED_LOGIN => 'yellow',
        };
    }

    /**
     * Get all values for use in filters.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Tab groupings for the frontend filter tabs.
     */
    public static function tabGroups(): array
    {
        return [
            'all' => self::values(),
            'login' => [self::LOGIN->value],
            'logout' => [self::LOGOUT->value],
            'create' => [self::CREATED->value],
            'update' => [self::UPDATED->value],
            'archive' => [self::ARCHIVED->value],
            'delete' => [self::DELETED->value],
            'blocked' => [self::BLOCKED->value, self::FAILED_LOGIN->value],
        ];
    }
}
