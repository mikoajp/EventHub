<?php

namespace App\Domain\Ticket\Service;

use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\Event;
use App\Repository\TicketRepository;

class TicketAvailabilityChecker
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {}

    public function isAvailable(TicketType $ticketType, int $requestedQuantity): bool
    {
        $soldTickets = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [\App\Enum\TicketStatus::PURCHASED->value, \App\Enum\TicketStatus::RESERVED->value]
        ]);

        $availableQuantity = $ticketType->getQuantity() - $soldTickets;

        return $availableQuantity >= $requestedQuantity;
    }

    public function getAvailableQuantity(TicketType $ticketType): int
    {
        $soldTickets = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [\App\Enum\TicketStatus::PURCHASED->value, \App\Enum\TicketStatus::RESERVED->value]
        ]);

        return max(0, $ticketType->getQuantity() - $soldTickets);
    }

    public function checkEventAvailability(Event $event): array
    {
        $availability = [];
        
        foreach ($event->getTicketTypes() as $ticketType) {
            $availability[$ticketType->getId()->toString()] = [
                'name' => $ticketType->getName(),
                'price' => $ticketType->getPrice(),
                'total_quantity' => $ticketType->getQuantity(),
                'available_quantity' => $this->getAvailableQuantity($ticketType),
                'is_available' => $this->isAvailable($ticketType, 1)
            ];
        }

        return $availability;
    }

    public function reserveTickets(TicketType $ticketType, int $quantity): bool
    {
        if (!$this->isAvailable($ticketType, $quantity)) {
            return false;
        }

        // In a real implementation, you might want to create temporary reservations
        // that expire after a certain time (e.g., 15 minutes)
        
        return true;
    }
}