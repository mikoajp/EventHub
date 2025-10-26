<?php

namespace App\MessageHandler\Query\Event;

use App\Message\Query\Event\GetEventsWithFiltersQuery;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetEventsWithFiltersHandler
{
    private const CACHE_TTL = 300; // 5 minutes

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

        return $this->cache->get(
            $cacheKey,
            fn() => $this->eventRepository->findEventsWithFilters(
                $query->filters,
                $query->sorting,
                $query->page,
                $query->limit
            ),
            self::CACHE_TTL
        );
    }
}
