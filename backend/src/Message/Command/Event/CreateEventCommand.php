<?php

namespace App\Message\Command\Event;

final readonly class CreateEventCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public \DateTimeImmutable $eventDate,
        public string $venue,
        public int $maxTickets,
        public string $organizerId,
        public array $ticketTypes = []
    ) {}
}
