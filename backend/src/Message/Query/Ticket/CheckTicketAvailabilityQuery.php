<?php

namespace App\Message\Query\Ticket;

final readonly class CheckTicketAvailabilityQuery
{
    public function __construct(
        public string $eventId,
        public string $ticketTypeId,
        public int $quantity
    ) {}
}
