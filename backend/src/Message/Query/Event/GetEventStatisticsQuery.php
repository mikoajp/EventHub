<?php

namespace App\Message\Query\Event;

final readonly class GetEventStatisticsQuery
{
    public function __construct(
        public string $eventId,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null
    ) {}
}
