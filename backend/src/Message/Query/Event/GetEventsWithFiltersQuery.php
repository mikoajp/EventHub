<?php

namespace App\Message\Query\Event;

final readonly class GetEventsWithFiltersQuery
{
    public function __construct(
        public array $filters = [],
        public array $sorting = [],
        public int $page = 1,
        public int $limit = 20
    ) {}
}
