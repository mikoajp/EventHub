<?php

namespace App\Message\Event;

final readonly class EventCancelledEvent
{
    public function __construct(
        public string $eventId,
        public string $reason,
        public \DateTimeImmutable $occurredAt
    ) {}
}
