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
        private TicketRepository $ticketRepository,
        private CacheInterface $cache
    ) {}

    /**
     * @deprecated Use CheckTicketAvailabilityQuery with query bus instead
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
    public function checkTicketAvailability(string $eventId, string $ticketTypeId, int $quantity = 1): array
    {
        $cacheKey = self::CACHE_KEY_AVAILABILITY_PREFIX . "{$eventId}.{$ticketTypeId}.{$quantity}";

        return $this->cache->get($cacheKey, function() use ($eventId, $ticketTypeId, $quantity) {
            return $this->ticketRepository->checkAvailability($eventId, $ticketTypeId, $quantity);
        }, self::CACHE_TTL_AVAILABILITY);
    }

    /**
     * @deprecated Use CheckTicketAvailabilityQuery for event availability
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
    public function getEventAvailability(Event $event): array
    {
        throw new \LogicException(
            'This method is deprecated. Use CheckTicketAvailabilityQuery with query bus instead.'
        );
    }

    /**
     * @deprecated Use PurchaseTicketCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function purchaseTicket(User $user, Event $event, TicketType $ticketType): array
    {
        throw new \LogicException(
            'This method is deprecated. Use PurchaseTicketCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new PurchaseTicketCommand(...))'
        );
    }

    /**
     * @deprecated Use PurchaseTicketCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function purchaseTicketByIds(User $user, string $eventId, string $ticketTypeId): array
    {
        throw new \LogicException(
            'This method is deprecated. Use PurchaseTicketCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new PurchaseTicketCommand(...))'
        );
    }

    /**
     * @deprecated Consider creating ConfirmTicketPurchaseCommand
     * This method is kept for backward compatibility only.
     */
    public function confirmTicketPurchase(string $ticketId, string $paymentId): void
    {
        throw new \LogicException(
            'This method is deprecated. Consider creating ConfirmTicketPurchaseCommand.'
        );
    }

    /**
     * @deprecated Use GetUserTicketsQuery with query bus instead
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
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

    /**
     * @deprecated Use CancelTicketCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function cancelTicket(string $ticketId, User $user, ?string $reason = null): void
    {
        throw new \LogicException(
            'This method is deprecated. Use CancelTicketCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new CancelTicketCommand(...))'
        );
    }
}