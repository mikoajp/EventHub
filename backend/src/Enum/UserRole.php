<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ORGANIZER = 'ROLE_ORGANIZER';
    case ADMIN = 'ROLE_ADMIN';

    /**
     * Get all role values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $role) => $role->value, self::cases());
    }

    /**
     * Check if a role string is valid
     */
    public static function isValid(string $role): bool
    {
        return in_array($role, self::values(), true);
    }

    /**
     * Check if this role has admin privileges
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if this role has organizer privileges
     */
    public function isOrganizer(): bool
    {
        return $this === self::ORGANIZER || $this === self::ADMIN;
    }

    /**
     * Get label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::USER => 'User',
            self::ORGANIZER => 'Event Organizer',
            self::ADMIN => 'Administrator',
        };
    }
}
