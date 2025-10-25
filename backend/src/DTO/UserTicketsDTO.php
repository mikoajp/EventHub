<?php

namespace App\DTO;

final readonly class UserTicketsDTO
{
    /** @param array<int, array<string, mixed>> $tickets */
    public function __construct(
        public array $tickets = [],
    ) {}
}
