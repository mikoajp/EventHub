<?php

namespace App\MessageHandler\Command\Ticket;

use App\Message\Command\Ticket\CancelTicketCommand;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Domain\Ticket\Service\TicketDomainService;
use App\Infrastructure\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class CancelTicketHandler
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository,
        private TicketDomainService $ticketDomainService,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CancelTicketCommand $command): void
    {
        $this->logger->info('Processing cancel ticket command', [
            'ticket_id' => $command->ticketId,
            'user_id' => $command->userId,
            'reason' => $command->reason
        ]);

        $ticket = $this->ticketRepository->find(Uuid::fromString($command->ticketId));
        if (!$ticket) {
            throw new \InvalidArgumentException('Ticket not found');
        }

        $user = $this->userRepository->find(Uuid::fromString($command->userId));
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Check ownership
        if ($ticket->getUser() !== $user) {
            throw new \InvalidArgumentException('You can only cancel your own tickets');
        }

        // Cancel the ticket
        $this->ticketDomainService->cancelTicket($ticket, $command->reason);
        
        $this->entityManager->flush();

        // Invalidate cache
        $eventId = $ticket->getEvent()->getId()->toString();
        $this->cache->deletePattern('ticket.availability.' . $eventId . '*');
        $this->cache->delete('ticket.availability.event.' . $eventId);
        $this->cache->delete('user.tickets.' . $command->userId);

        $this->logger->info('Ticket cancelled successfully', [
            'ticket_id' => $command->ticketId
        ]);
    }
}
