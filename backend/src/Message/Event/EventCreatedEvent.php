<?php

namespace App\Message\Event;

final readonly class EventCreatedEvent
{
    public function __construct(
        public string $eventId,
        public string $organizerId,
        public \DateTimeImmutable $occurredAt
    ) {}
}