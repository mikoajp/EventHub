<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Get all status values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Check if order can be cancelled
     */
    public function isCancellable(): bool
    {
        return match($this) {
            self::PENDING, self::PAID => true,
            self::CANCELLED, self::REFUNDED => false,
        };
    }

    /**
     * Check if order can be refunded
     */
    public function isRefundable(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if order is finalized
     */
    public function isFinalized(): bool
    {
        return match($this) {
            self::PAID, self::CANCELLED, self::REFUNDED => true,
            self::PENDING => false,
        };
    }

    /**
     * Get label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }
}
