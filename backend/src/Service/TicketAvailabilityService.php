<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Repository\TicketRepository;

final readonly class TicketAvailabilityService
{
    public function __construct(
        private TicketRepository $ticketRepository
    ) {}

    public function isAvailable(TicketType $ticketType, int $requestedQuantity): bool
    {
        $soldTickets = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        $availableQuantity = $ticketType->getQuantity() - $soldTickets;

        return $availableQuantity >= $requestedQuantity;
    }

}