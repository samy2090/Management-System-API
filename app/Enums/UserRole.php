<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case MANAGER = 'manager';

    /**
     * Get all role values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role labels for display
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'User',
            self::MANAGER => 'Manager',
        };
    }

    /**
     * Check if the role is manager
     */
    public function isManager(): bool
    {
        return $this === self::MANAGER;
    }

    /**
     * Check if the role is user
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }
}