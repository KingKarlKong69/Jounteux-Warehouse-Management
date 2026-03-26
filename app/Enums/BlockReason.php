<?php

namespace App\Enums;

enum BlockReason: string
{
    case FAILED_LOGIN_ATTEMPTS = 'failed_login_attempts';
    case MANUAL_ADMIN_BLOCK = 'manual_admin_block';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FAILED_LOGIN_ATTEMPTS => 'Failed Login Attempts',
            self::MANUAL_ADMIN_BLOCK => 'Manually Blocked by Admin',
        };
    }

    /**
     * Badge colour for the frontend.
     */
    public function color(): string
    {
        return match ($this) {
            self::FAILED_LOGIN_ATTEMPTS => 'red',
            self::MANUAL_ADMIN_BLOCK => 'orange',
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
