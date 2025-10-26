<?php

namespace App\Domain\Event\Service;

use App\Entity\Event;
use App\Entity\TicketType;
use App\Repository\EventRepository;

class EventCalculationService
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {}

    public function calculateTicketsSold(Event $event): int
    {
        $statistics = $this->eventRepository->getTicketSalesStatistics($event);
        return $statistics['total'];
    }

    public function calculateAvailableTickets(Event $event): int
    {
        $ticketsSold = $this->calculateTicketsSold($event);
        return $event->getMaxTickets() - $ticketsSold;
    }

    public function calculateTotalRevenue(Event $event): float
    {
        $statistics = $this->eventRepository->getRevenueStatistics($event);
        return $statistics['total'];
    }

    public function calculateOccupancyRate(Event $event): float
    {
        if ($event->getMaxTickets() === 0) {
            return 0.0;
        }

        $ticketsSold = $this->calculateTicketsSold($event);
        return ($ticketsSold / $event->getMaxTickets()) * 100;
    }

    public function getOccupancyRate(Event $event): float
    {
        return round($this->calculateOccupancyRate($event), 2);
    }

    public function getAttendeesCount(Event $event): int
    {
        return $event->getAttendees()->count();
    }

    public function getOrdersCount(Event $event): int
    {
        return $event->getOrders()->count();
    }

    public function getDaysUntilEvent(Event $event): int
    {
        $now = new \DateTime();
        $eventDate = $event->getEventDate();
        
        if (!$eventDate) {
            return 0;
        }

        $diff = $now->diff($eventDate);
        return $eventDate > $now ? $diff->days : -$diff->days;
    }

    public function hasAvailableTicketType(Event $event): bool
    {
        foreach ($event->getTicketTypes() as $ticketType) {
            if ($ticketType->getAvailableQuantity() > 0) {
                return true;
            }
        }
        return false;
    }

    public function getTicketTypeByName(Event $event, string $name): ?TicketType
    {
        foreach ($event->getTicketTypes() as $ticketType) {
            if ($ticketType->getName() === $name) {
                return $ticketType;
            }
        }
        return null;
    }
}
