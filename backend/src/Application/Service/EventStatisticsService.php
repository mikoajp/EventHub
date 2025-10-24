<?php

namespace App\Application\Service;

use App\Domain\Analytics\Service\EventStatisticsCalculator;
use App\Infrastructure\Cache\CacheInterface;
use App\Repository\EventRepository;

final readonly class EventStatisticsService
{
    public function __construct(
        private EventStatisticsCalculator $calculator,
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    /**
     * Get full event statistics with caching
     */
    public function getEventStatistics(string $eventId): array
    {
        $cacheKey = 'event.statistics.' . $eventId;

        return $this->cache->get(
            $cacheKey,
            fn() => $this->calculateStatistics($eventId),
            3600 // 1 hour cache
        );
    }

    /**
     * Update and recalculate statistics (after ticket purchase)
     */
    public function updateEventStatistics(string $eventId): void
    {
        // Recalculate and store in cache
        $statistics = $this->calculateStatistics($eventId);
        $cacheKey = 'event.statistics.' . $eventId;
        $this->cache->set($cacheKey, $statistics, 3600);
    }

    /**
     * Invalidate statistics cache
     */
    public function invalidateCache(string $eventId): void
    {
        $this->cache->delete('event.statistics.' . $eventId);
        $this->cache->delete('event.' . $eventId);
        $this->cache->deletePattern('events.*');
    }

    /**
     * Private method to calculate statistics
     */
    private function calculateStatistics(string $eventId): array
    {
        $event = $this->eventRepository->findByUuid($eventId)
            ?? $this->eventRepository->find($eventId);

        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }

        return $this->calculator->calculateEventStatistics($event);
    }
}
