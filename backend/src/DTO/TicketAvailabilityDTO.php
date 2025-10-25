<?php

namespace App\DTO;

final readonly class TicketAvailabilityDTO
{
    public function __construct(
        public string $eventId,
        public string $ticketTypeId,
        public int $requestedQuantity,
        public bool $available,
        public int $availableQuantity,
    ) {}
}
