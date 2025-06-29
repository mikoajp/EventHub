<?php

namespace App\Message\Command\Event;

final readonly class PublishEventCommand
{
    public function __construct(
        public string $eventId,
        public string $userId
    ) {}
}