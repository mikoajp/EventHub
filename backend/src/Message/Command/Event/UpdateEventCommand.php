<?php

namespace App\Message\Command\Event;

use App\DTO\EventDTO;

final readonly class UpdateEventCommand
{
    public function __construct(
        public string $eventId,
        public string $userId,
        public EventDTO $eventDTO
    ) {}
}
