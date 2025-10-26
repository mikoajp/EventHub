<?php

namespace App\Message\Command\Ticket;

final readonly class CancelTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $userId,
        public ?string $reason = null
    ) {}
}
