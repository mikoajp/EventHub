<?php

namespace App\Enum;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    /**
     * Get all status values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Check if status allows modifications
     */
    public function allowsModification(): bool
    {
        return match($this) {
            self::DRAFT, self::PUBLISHED => true,
            self::CANCELLED, self::COMPLETED => false,
        };
    }

    /**
     * Check if status allows publishing
     */
    public function allowsPublishing(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if status allows cancellation
     */
    public function allowsCancellation(): bool
    {
        return match($this) {
            self::DRAFT, self::PUBLISHED => true,
            self::CANCELLED, self::COMPLETED => false,
        };
    }

    /**
     * Get label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }
}
