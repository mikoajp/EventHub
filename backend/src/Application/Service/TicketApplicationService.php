<?php

namespace App\Application\Service;

use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Domain\Ticket\Service\TicketDomainService;
use App\Entity\Event;
use App\Entity\TicketType;
use App\Entity\User;
use App\Infrastructure\Cache\CacheInterface;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\TicketTypeRepository;

final readonly class TicketApplicationService
{
    private const CACHE_TTL_AVAILABILITY = 300; // 5 minutes
    private const CACHE_KEY_AVAILABILITY_PREFIX = 'ticket.availability.';
    private const CACHE_KEY_USER_TICKETS_PREFIX = 'user.tickets.';

    public function __construct(
        private TicketDomainService $ticketDomainService,
        private TicketAvailabilityChecker $ticketAvailabilityChecker,
        private TicketRepository $ticketRepository,
        private EventRepository $eventRepository,
        private TicketTypeRepository $ticketTypeRepository,
        private CacheInterface $cache
    ) {}

    public function checkTicketAvailability(string $eventId, string $ticketTypeId, int $quantity = 1): array
    {
        $cacheKey = self::CACHE_KEY_AVAILABILITY_PREFIX . "{$eventId}.{$ticketTypeId}.{$quantity}";

        return $this->cache->get($cacheKey, function() use ($eventId, $ticketTypeId, $quantity) {
            return $this->ticketRepository->checkAvailability($eventId, $ticketTypeId, $quantity);
        }, self::CACHE_TTL_AVAILABILITY);
    }

    public function getEventAvailability(Event $event): array
    {
        $cacheKey = self::CACHE_KEY_AVAILABILITY_PREFIX . 'event.' . $event->getId()->toString();

        return $this->cache->get($cacheKey, function() use ($event) {
            return $this->ticketAvailabilityChecker->checkEventAvailability($event);
        }, self::CACHE_TTL_AVAILABILITY);
    }

    public function purchaseTicket(User $user, Event $event, TicketType $ticketType): array
    {
        // Check availability
        if (!$this->ticketAvailabilityChecker->isAvailable($ticketType, 1)) {
            throw new \DomainException('Ticket is not available');
        }

        // Create ticket
        $ticket = $this->ticketDomainService->createTicket($user, $event, $ticketType);

        // Invalidate cache
        $this->invalidateAvailabilityCache($event);
        $this->invalidateUserTicketsCache($user);

        return [
            'ticket_id' => $ticket->getId()->toString(),
            'status' => $ticket->getStatus(),
            'price' => $ticket->getPrice(),
            'event' => $event->getName(),
            'ticket_type' => $ticketType->getName()
        ];
    }

    public function purchaseTicketByIds(User $user, string $eventId, string $ticketTypeId): array
    {
        $event = $this->eventRepository->findByUuid($eventId) ?? $this->eventRepository->find($eventId);
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }
        $ticketType = $this->ticketTypeRepository->find($ticketTypeId);
        if (!$ticketType) {
            throw new \InvalidArgumentException('Ticket type not found');
        }
        return $this->purchaseTicket($user, $event, $ticketType);
    }

    public function confirmTicketPurchase(string $ticketId, string $paymentId): void
    {
        $ticket = $this->ticketRepository->find($ticketId);
        if (!$ticket) {
            throw new \InvalidArgumentException('Ticket not found');
        }

        $this->ticketDomainService->confirmTicketPurchase($ticket, $paymentId);

        // Invalidate cache
        $this->invalidateUserTicketsCache($ticket->getUser());
    }

    public function getUserTickets(User $user): array
    {
        $cacheKey = self::CACHE_KEY_USER_TICKETS_PREFIX . $user->getId()->toString();

        return $this->cache->get($cacheKey, function() use ($user) {
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
        }, 1800); // 30 minutes
    }

    public function cancelTicket(string $ticketId, User $user, string $reason = null): void
    {
        $ticket = $this->ticketRepository->find($ticketId);
        if (!$ticket) {
            throw new \InvalidArgumentException('Ticket not found');
        }

        if ($ticket->getUser() !== $user) {
            throw new \InvalidArgumentException('You can only cancel your own tickets');
        }

        $this->ticketDomainService->cancelTicket($ticket, $reason);

        // Invalidate cache
        $this->invalidateAvailabilityCache($ticket->getEvent());
        $this->invalidateUserTicketsCache($user);
    }

    private function invalidateAvailabilityCache(Event $event): void
    {
        $this->cache->deletePattern(self::CACHE_KEY_AVAILABILITY_PREFIX . $event->getId()->toString() . '*');
        $this->cache->delete(self::CACHE_KEY_AVAILABILITY_PREFIX . 'event.' . $event->getId()->toString());
    }

    private function invalidateUserTicketsCache(User $user): void
    {
        $this->cache->delete(self::CACHE_KEY_USER_TICKETS_PREFIX . $user->getId()->toString());
    }
}