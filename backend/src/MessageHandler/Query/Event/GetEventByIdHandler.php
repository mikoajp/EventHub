<?php

namespace App\MessageHandler\Query\Event;

use App\Entity\Event;
use App\Message\Query\Event\GetEventByIdQuery;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetEventByIdHandler
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(GetEventByIdQuery $query): ?Event
    {
        return $this->cache->get(
            'event.' . $query->eventId,
            fn() => $this->eventRepository->findByUuid($query->eventId),
            self::CACHE_TTL
        );
    }
}
