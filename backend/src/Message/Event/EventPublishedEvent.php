<?php

namespace App\Message\Event;

final readonly class EventPublishedEvent
{
    public function __construct(
        public string $eventId,
        public string $publishedBy,
        public \DateTimeImmutable $publishedAt
    ) {}
}