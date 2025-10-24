<?php

namespace App\Service;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class EventStatisticsService
{
    public function __construct(
        private EventRepository $eventRepository,
        private TicketRepository $ticketRepository,
        private CacheInterface $cache
    ) {}

    public function updateEventStatistics(string $eventId): void
    {
        $statistics = $this->calculateStatistics($eventId);
        
        $this->cache->get(
            "event_statistics_{$eventId}",
            function (ItemInterface $item) use ($statistics) {
                $item->expiresAfter(3600); // 1 hour
                return $statistics;
            }
        );
    }

    public function getEventStatistics(string $eventId): array
    {
        return $this->cache->get(
            "event_statistics_{$eventId}",
            function (ItemInterface $item) use ($eventId) {
                $item->expiresAfter(3600);
                return $this->calculateStatistics($eventId);
            }
        );
    }

    public function invalidateCache(string $eventId): void
    {
        $this->cache->delete("event_statistics_{$eventId}");
    }

    private function calculateStatistics(string $eventId): array
    {
        $event = $this->eventRepository->findOneBy(['id' => $eventId]);
        
        if (!$event) {
            return [];
        }

        return [
            'total_tickets' => $event->getMaxTickets(),
            'sold_tickets' => $event->getTicketsSold(),
            'available_tickets' => $event->getAvailableTickets(),
            'total_revenue' => $this->ticketRepository->getTotalRevenue($event),
            'sales_by_type' => $this->ticketRepository->getSalesByTicketType($event),
            'sales_timeline' => $this->ticketRepository->getSalesTimeline($event),
            'conversion_rate' => $this->calculateConversionRate($event)
        ];
    }

    private function calculateConversionRate($event): float
    {
        $totalViews = 1000; // This would come from analytics service
        $totalSales = $event->getTicketsSold();
        
        return $totalViews > 0 ? ($totalSales / $totalViews) * 100 : 0;
    }
}