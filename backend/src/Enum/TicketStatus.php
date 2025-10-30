<?php

namespace App\Enum;

enum TicketStatus: string
{
    case RESERVED = 'reserved';
    case PURCHASED = 'purchased';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case USED = 'used';

    /**
     * Get all status values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Check if ticket can be cancelled
     */
    public function isCancellable(): bool
    {
        return match($this) {
            self::RESERVED, self::PURCHASED => true,
            self::CANCELLED, self::REFUNDED, self::USED => false,
        };
    }

    /**
     * Check if ticket can be used
     */
    public function isUsable(): bool
    {
        return $this === self::PURCHASED;
    }

    /**
     * Check if ticket is active
     */
    public function isActive(): bool
    {
        return match($this) {
            self::RESERVED, self::PURCHASED => true,
            self::CANCELLED, self::REFUNDED, self::USED => false,
        };
    }

    /**
     * Get label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::RESERVED => 'Reserved',
            self::PURCHASED => 'Purchased',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::USED => 'Used',
        };
    }
}
