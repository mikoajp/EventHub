<?php

declare(strict_types=1);

namespace App\MessageHandler\Query\Ticket;

use App\Message\Query\Ticket\CheckTicketAvailabilityQuery;
use App\Repository\TicketRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckTicketAvailabilityHandler
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'ticket.availability.';

    public function __construct(
        private TicketRepository $ticketRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(CheckTicketAvailabilityQuery $query): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . "{$query->eventId}.{$query->ticketTypeId}.{$query->quantity}";

        return $this->cache->get($cacheKey, function() use ($query) {
            return $this->ticketRepository->checkAvailability(
                $query->eventId,
                $query->ticketTypeId,
                $query->quantity
            );
        }, self::CACHE_TTL);
    }
}



