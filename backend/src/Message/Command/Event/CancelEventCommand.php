<?php

namespace App\Message\Command\Event;

final readonly class CancelEventCommand
{
    public function __construct(
        public string $eventId,
        public ?string $reason = null
    ) {}
}