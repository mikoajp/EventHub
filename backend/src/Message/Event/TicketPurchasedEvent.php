<?php

namespace App\Message\Event;

final readonly class TicketPurchasedEvent
{
    public function __construct(
        public string $ticketId,
        public string $eventId,
        public string $userId,
        public int $amount,
        public \DateTimeImmutable $occurredAt
    ) {}
}
