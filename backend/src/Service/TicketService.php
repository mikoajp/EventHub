<?php

namespace App\Service;

use App\Repository\TicketRepository;
use Psr\Cache\InvalidArgumentException;

class TicketService
{
    private const CACHE_TTL_AVAILABILITY = 300; // 5 minutes for availability
    private const CACHE_KEY_AVAILABILITY_PREFIX = 'ticket.availability.';

    public function __construct(
        private readonly TicketRepository       $ticketRepository,
        private readonly CacheService           $cacheService
    ) {}

    /**
     * Check ticket availability
     * @throws InvalidArgumentException
     */
    public function checkTicketAvailability(string $eventId, string $ticketTypeId, int $quantity = 1): array
    {
        $cacheKey = self::CACHE_KEY_AVAILABILITY_PREFIX . "{$eventId}.{$ticketTypeId}.{$quantity}";

        return $this->cacheService->get($cacheKey, function() use ($eventId, $ticketTypeId, $quantity) {
            return $this->ticketRepository->checkAvailability($eventId, $ticketTypeId, $quantity);
        }, self::CACHE_TTL_AVAILABILITY);
    }

}