<?php

namespace App\MessageHandler\Query\Ticket;

use App\Message\Query\Ticket\GetUserTicketsQuery;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class GetUserTicketsHandler
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository,
        private CacheInterface $cache
    ) {}

    public function __invoke(GetUserTicketsQuery $query): array
    {
        $cacheKey = 'user.tickets.' . $query->userId;

        return $this->cache->get($cacheKey, function() use ($query) {
            $user = $this->userRepository->find(Uuid::fromString($query->userId));
            if (!$user) {
                throw new \InvalidArgumentException('User not found');
            }

            $tickets = $this->ticketRepository->findBy(['user' => $user]);
            
            return array_map(function($ticket) {
                return [
                    'id' => $ticket->getId()->toString(),
                    'event_name' => $ticket->getEvent()->getName(),
                    'event_date' => $ticket->getEvent()->getEventDate()->format('c'),
                    'ticket_type' => $ticket->getTicketType()->getName(),
                    'price' => $ticket->getPrice(),
                    'status' => $ticket->getStatus(),
                    'purchase_date' => $ticket->getPurchasedAt()?->format('c')
                ];
            }, $tickets);
        }, self::CACHE_TTL);
    }
}
