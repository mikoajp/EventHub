<?php

namespace App\Message\Event;

final readonly class TicketReservedEvent
{
    public function __construct(
        public string $ticketId,
        public string $eventId,
        public string $userId,
        public \DateTimeImmutable $occurredAt
    ) {}
}