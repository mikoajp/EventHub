<?php

namespace App\Domain\Event\Service;

use App\Entity\Event;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\TicketType;

/**
 * Service responsible for event-related calculations and aggregations.
 * Moved from Event entity to comply with SRP.
 */
final readonly class EventCalculationService
{
    // ==========================================
    // Ticket Sales Calculations
    // ==========================================

    /**
     * Calculate total tickets sold for an event.
     * This is a temporary in-memory calculation.
     * For production, use EventRepository::getTicketSalesStatistics() for optimized queries.
     */
    public function calculateTicketsSold(Event $event): int
    {
        if ($event->getOrders()->count() > 0) {
            return $event->getOrders()->reduce(function (int $total, Order $order) {
                return $total + $order->getOrderItems()->reduce(function (int $itemTotal, $orderItem) {
                    return $itemTotal + $orderItem->getQuantity();
                }, 0);
            }, 0);
        }

        return $event->getTickets()->filter(fn(Ticket $ticket) =>
            $ticket->getStatus() === 'purchased'
        )->count();
    }

    /**
     * Calculate available tickets for an event
     */
    public function calculateAvailableTickets(Event $event): int
    {
        $ticketsSold = $this->calculateTicketsSold($event);
        return $event->getMaxTickets() - $ticketsSold;
    }

    // ==========================================
    // Revenue Calculations
    // ==========================================

    /**
     * Calculate total revenue for an event.
     * For production, use EventRepository::getRevenueStatistics() for optimized queries.
     */
    public function calculateTotalRevenue(Event $event): float
    {
        if ($event->getOrders()->count() > 0) {
            return $event->getOrders()->reduce(function (float $total, Order $order) {
                return $total + $order->getTotalAmount();
            }, 0.0);
        }

        return $event->getTickets()
            ->filter(fn(Ticket $ticket) => $ticket->getStatus() === 'purchased')
            ->reduce(function (float $total, Ticket $ticket) {
                return $total + $ticket->getPrice();
            }, 0.0);
    }

    // ==========================================
    // Occupancy Calculations
    // ==========================================

    /**
     * Calculate occupancy rate as percentage
     */
    public function calculateOccupancyRate(Event $event): float
    {
        if ($event->getMaxTickets() === 0) {
            return 0.0;
        }

        $ticketsSold = $this->calculateTicketsSold($event);
        return ($ticketsSold / $event->getMaxTickets()) * 100;
    }

    /**
     * Calculate occupancy rate rounded to 2 decimals
     */
    public function getOccupancyRate(Event $event): float
    {
        return round($this->calculateOccupancyRate($event), 2);
    }

    // ==========================================
    // Attendee & Order Counts
    // ==========================================

    public function getAttendeesCount(Event $event): int
    {
        return $event->getAttendees()->count();
    }

    public function getOrdersCount(Event $event): int
    {
        return $event->getOrders()->count();
    }

    // ==========================================
    // Date Calculations
    // ==========================================

    /**
     * Calculate days until event (negative if past)
     */
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

    // ==========================================
    // Ticket Type Availability
    // ==========================================

    /**
     * Check if event has any available ticket type
     */
    public function hasAvailableTicketType(Event $event): bool
    {
        foreach ($event->getTicketTypes() as $ticketType) {
            if ($ticketType->getAvailableQuantity() > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get ticket type by name
     */
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
