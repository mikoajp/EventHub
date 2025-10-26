<?php

namespace App\MessageHandler\Query\Event;

use App\Message\Query\Event\GetFilterOptionsQuery;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetFilterOptionsHandler
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(GetFilterOptionsQuery $query): array
    {
        return $this->cache->get(
            'events.filter_options',
            fn() => [
                'venues' => $this->eventRepository->getUniqueVenues(),
                'priceRange' => $this->eventRepository->getPriceRange(),
                'statuses' => [
                    ['value' => 'published', 'label' => 'Published'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                    ['value' => 'completed', 'label' => 'Completed']
                ]
            ],
            self::CACHE_TTL
        );
    }
}
