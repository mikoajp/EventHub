<?php

namespace App\Message\Command\Ticket;

final readonly class PurchaseTicketCommand
{
    public function __construct(
        public string $eventId,
        public string $ticketTypeId,
        public int $quantity,
        public string $userId,
        public string $paymentMethodId
    ) {}
}