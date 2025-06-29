<?php

namespace App\Message\Event;

final readonly class EventPublishNotificationEvent
{
    public function __construct(
        public string $eventId,
        public string $eventName,
        public string $publisherId,
        public string $publisherEmail,
        public string $publisherName,
        public \DateTimeImmutable $publishedAt,
        public \DateTimeInterface $eventDate,
        public string $venue,
        public ?string $description = null
    ) {}
}