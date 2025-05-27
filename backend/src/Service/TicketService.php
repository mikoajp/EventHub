<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class TicketService
{
    private const CACHE_TTL_EVENT_STATS = 900; // 15 minutes
    private const CACHE_TTL_SALES = 1800; // 30 minutes
    private const CACHE_TTL_USER_TICKETS = 1200; // 20 minutes
    private const CACHE_KEY_EVENT_STATS_PREFIX = 'event.stats.';
    private const CACHE_KEY_SALES_TIMELINE_PREFIX = 'event.sales.timeline.';
    private const CACHE_KEY_TOTAL_REVENUE_PREFIX = 'event.revenue.';
    private const CACHE_KEY_SALES_BY_TYPE_PREFIX = 'event.sales.by_type.';
    private const CACHE_KEY_USER_TICKETS_PREFIX = 'user.tickets.';

    public function __construct(
        private TicketRepository $ticketRepository,
        private EntityManagerInterface $entityManager,
        private CacheService $cacheService
    ) {}

    /**
     * Get event statistics with cache
     */
    public function getEventStatistics(
        Event $event,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $fromKey = $from ? $from->format('Ymd') : 'all';
        $toKey = $to ? $to->format('Ymd') : 'all';
        $cacheKey = self::CACHE_KEY_EVENT_STATS_PREFIX . $event->getId() . ".{$fromKey}_{$toKey}";
        
        return $this->cacheService->get($cacheKey, function() use ($event, $from, $to) {
            return $this->ticketRepository->getEventStatistics($event, $from, $to);
        }, self::CACHE_TTL_EVENT_STATS);
    }

    /**
     * Get sales timeline with cache
     */
    public function getSalesTimeline(
        Event $event,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $fromKey = $from ? $from->format('Ymd') : 'all';
        $toKey = $to ? $to->format('Ymd') : 'all';
        $cacheKey = self::CACHE_KEY_SALES_TIMELINE_PREFIX . $event->getId() . ".{$fromKey}_{$toKey}";
        
        return $this->cacheService->get($cacheKey, function() use ($event, $from, $to) {
            return $this->ticketRepository->getSalesTimeline($event, $from, $to);
        }, self::CACHE_TTL_SALES);
    }

    /**
     * Get total revenue with cache
     */
    public function getTotalRevenue(Event $event): int
    {
        $cacheKey = self::CACHE_KEY_TOTAL_REVENUE_PREFIX . $event->getId();
        
        return $this->cacheService->get($cacheKey, function() use ($event) {
            return $this->ticketRepository->getTotalRevenue($event);
        }, self::CACHE_TTL_SALES);
    }

    /**
     * Get sales by ticket type with cache
     */
    public function getSalesByTicketType(Event $event): array
    {
        $cacheKey = self::CACHE_KEY_SALES_BY_TYPE_PREFIX . $event->getId();
        
        return $this->cacheService->get($cacheKey, function() use ($event) {
            return $this->ticketRepository->getSalesByTicketType($event);
        }, self::CACHE_TTL_SALES);
    }

    /**
     * Find user tickets for event with cache
     */
    public function findUserTicketsForEvent(User $user, Event $event): array
    {
        $cacheKey = self::CACHE_KEY_USER_TICKETS_PREFIX . $user->getId() . '.event.' . $event->getId();
        
        return $this->cacheService->get($cacheKey, function() use ($user, $event) {
            return $this->ticketRepository->findUserTicketsForEvent(
                $user->getId()->toString(), 
                $event->getId()->toString()
            );
        }, self::CACHE_TTL_USER_TICKETS);
    }

    /**
     * Cancel expired reservations and invalidate related cache
     */
    public function cancelExpiredReservations(DateTimeImmutable $expiryTime): int
    {
        $count = $this->ticketRepository->cancelExpiredReservations($expiryTime);
        
        if ($count > 0) {
            // If any reservations were cancelled, clear all ticket-related cache
            // This is a broad invalidation since we don't know which specific events were affected
            $this->cacheService->deletePattern(self::CACHE_KEY_EVENT_STATS_PREFIX . '*');
            $this->cacheService->deletePattern(self::CACHE_KEY_SALES_TIMELINE_PREFIX . '*');
            $this->cacheService->deletePattern(self::CACHE_KEY_TOTAL_REVENUE_PREFIX . '*');
            $this->cacheService->deletePattern(self::CACHE_KEY_SALES_BY_TYPE_PREFIX . '*');
        }
        
        return $count;
    }

    /**
     * Save a ticket and invalidate related cache
     */
    public function saveTicket(Ticket $ticket): void
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
        
        $this->invalidateTicketCache($ticket);
    }

    /**
     * Invalidate all cache related to a ticket
     */
    private function invalidateTicketCache(Ticket $ticket): void
    {
        $event = $ticket->getEvent();
        $user = $ticket->getUser();
        
        // Invalidate event statistics
        $this->cacheService->deletePattern(self::CACHE_KEY_EVENT_STATS_PREFIX . $event->getId() . '*');
        $this->cacheService->delete(self::CACHE_KEY_TOTAL_REVENUE_PREFIX . $event->getId());
        $this->cacheService->delete(self::CACHE_KEY_SALES_BY_TYPE_PREFIX . $event->getId());
        $this->cacheService->deletePattern(self::CACHE_KEY_SALES_TIMELINE_PREFIX . $event->getId() . '*');
        
        // Invalidate user tickets
        if ($user) {
            $this->cacheService->delete(self::CACHE_KEY_USER_TICKETS_PREFIX . $user->getId() . '.event.' . $event->getId());
        }
    }
}
