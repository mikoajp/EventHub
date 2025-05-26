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

    public function getAvailableQuantity(TicketType $ticketType): int
    {
        $soldTickets = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        return max(0, $ticketType->getQuantity() - $soldTickets);
    }

    public function reserveExpiredTickets(): int
    {
        // Clean up expired reservations (older than 15 minutes)
        $expiryTime = new \DateTimeImmutable('-15 minutes');
        
        return $this->ticketRepository->cancelExpiredReservations($expiryTime);
    }
}