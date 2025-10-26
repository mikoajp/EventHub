<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Ticket;

use App\Message\Command\Ticket\ReserveTicketCommand;
use App\Repository\EventRepository;
use App\Repository\TicketTypeRepository;
use App\Repository\UserRepository;
use App\Domain\Ticket\Service\TicketDomainService;
use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Infrastructure\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class ReserveTicketHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private TicketTypeRepository $ticketTypeRepository,
        private UserRepository $userRepository,
        private TicketDomainService $ticketDomainService,
        private TicketAvailabilityChecker $availabilityChecker,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ReserveTicketCommand $command): string
    {
        $this->logger->info('Processing reserve ticket command', [
            'event_id' => $command->eventId,
            'ticket_type_id' => $command->ticketTypeId,
            'user_id' => $command->userId
        ]);

        $event = $this->eventRepository->findByUuid($command->eventId) ?? $this->eventRepository->find($command->eventId);
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $ticketType = $this->ticketTypeRepository->find($command->ticketTypeId);
        if (!$ticketType) {
            throw new \InvalidArgumentException('Ticket type not found');
        }

        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Check availability
        if (!$this->availabilityChecker->isAvailable($ticketType, 1)) {
            throw new \DomainException('Ticket is not available');
        }

        // Create ticket
        $ticket = $this->ticketDomainService->createTicket($user, $event, $ticketType);
        
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->deletePattern('ticket.availability.' . $event->getId()->toString() . '*');
        $this->cache->delete('ticket.availability.event.' . $event->getId()->toString());
        $this->cache->delete('user.tickets.' . $user->getId()->toString());

        $this->logger->info('Ticket reserved successfully', [
            'event_id' => $command->eventId,
            'ticket_id' => $ticket->getId()->toString()
        ]);

        return $ticket->getId()->toString();
    }
}
