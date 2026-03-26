<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case STAFF = 'staff';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::STAFF => 'Staff',
        };
    }

    /**
     * All values for validation / filtering.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
