<?php

namespace App\MessageHandler\Query\Event;

use App\Message\Query\Event\GetEventsWithFiltersQuery;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetEventsWithFiltersHandler
{
    private const CACHE_TTL = 0; // Cache disabled for debugging

    public function __construct(
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(GetEventsWithFiltersQuery $query): array
    {
        // Create cache key based on filters
        $cacheKey = 'events.filtered.' . md5(serialize([
            $query->filters, 
            $query->sorting, 
            $query->page, 
            $query->limit
        ]));
        
        // Debug logging
        error_log('[GetEventsHandler] Cache key: ' . $cacheKey);
        error_log('[GetEventsHandler] Filters status: ' . json_encode(is_array($query->filters) ? ($query->filters['status'] ?? null) : $query->filters->status));

        $result = $this->cache->get(
            $cacheKey,
            function() use ($query) {
                error_log('[GetEventsHandler] Cache MISS - fetching from DB');
                error_log('[GetEventsHandler] Query filters: ' . json_encode($query->filters));
                error_log('[GetEventsHandler] Calling Repository...');
                $events = $this->eventRepository->findEventsWithFilters(
                    $query->filters,
                    $query->sorting,
                    $query->page,
                    $query->limit
                );
                error_log('[GetEventsHandler] DB returned ' . count($events) . ' events');
                return $events;
            },
            self::CACHE_TTL
        );
        
        error_log('[GetEventsHandler] Returning ' . count($result) . ' events');
        return $result;
    }
}
