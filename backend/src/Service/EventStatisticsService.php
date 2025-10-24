<?php

namespace App\Service;

use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;

final class EventStatisticsService
{
    public function __construct(
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    public function updateEventStatistics(string $eventId): void
    {
        // Domain layer likely handles stats; keep placeholder for now
        // e.g., precompute aggregates if needed
    }

    public function invalidateCache(string $eventId): void
    {
        $this->cache->delete('event.' . $eventId);
        $this->cache->deletePattern('events.*');
    }
}
