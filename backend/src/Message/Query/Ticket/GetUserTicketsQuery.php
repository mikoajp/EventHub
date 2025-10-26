<?php

namespace App\Message\Query\Ticket;

final readonly class GetUserTicketsQuery
{
    public function __construct(
        public string $userId
    ) {}
}
