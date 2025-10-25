<?php

namespace App\DTO;

/**
 * Detailed event view DTO extending list item with computed metrics.
 * Immutable (readonly) and safe to expose to presentation layer.
 */
readonly class EventDetailsDTO extends EventListItemDTO
{
    public function __construct(
        // Base list item fields
        string $id,
        string $name,
        string $description,
        string $eventDate,
        string $eventDateFormatted,
        string $venue,
        int $maxTickets,
        string $status,
        string $statusLabel,
        ?string $publishedAt = null,
        ?string $createdAt = null,
        ?string $createdAtFormatted = null,
        array $organizer = ['id' => null, 'name' => null],
        array $ticketTypes = [],
        // Details-only fields
        public int $ticketsSold = 0,
        public int $availableTickets = 0,
        public int|float $totalRevenue = 0,
        public float $occupancyRate = 0.0,
        public int $attendeesCount = 0,
        public int $ordersCount = 0,
        public int $daysUntilEvent = 0,
        public bool $isUpcoming = false,
        public bool $isPast = false,
        public bool $isSoldOut = false,
        public bool $canBeModified = false,
        public bool $canBeCancelled = false,
        public bool $canBePublished = false,
        public bool $canBeCompleted = false,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            description: $description,
            eventDate: $eventDate,
            eventDateFormatted: $eventDateFormatted,
            venue: $venue,
            maxTickets: $maxTickets,
            status: $status,
            statusLabel: $statusLabel,
            publishedAt: $publishedAt,
            createdAt: $createdAt,
            createdAtFormatted: $createdAtFormatted,
            organizer: $organizer,
            ticketTypes: $ticketTypes,
        );
    }
}
