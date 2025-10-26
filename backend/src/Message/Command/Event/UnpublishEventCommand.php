<?php

namespace App\Message\Command\Event;

final readonly class UnpublishEventCommand
{
    public function __construct(
        public string $eventId,
        public string $userId
    ) {}
}
