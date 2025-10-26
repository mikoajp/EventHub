<?php

namespace App\Message\Query\Event;

final readonly class GetEventByIdQuery
{
    public function __construct(
        public string $eventId
    ) {}
}
