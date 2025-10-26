<?php

namespace App\MessageHandler\Command\Event;

use App\Message\Command\Event\UnpublishEventCommand;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Domain\Event\Service\EventDomainService;
use App\Infrastructure\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class UnpublishEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EventDomainService $eventDomainService,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(UnpublishEventCommand $command): void
    {
        $this->logger->info('Processing unpublish event command', [
            'event_id' => $command->eventId,
            'user_id' => $command->userId
        ]);

        $event = $this->eventRepository->find(Uuid::fromString($command->eventId));
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $user = $this->userRepository->find(Uuid::fromString($command->userId));
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Check permissions
        if (!$this->eventDomainService->canUserModifyEvent($event, $user)) {
            throw new \InvalidArgumentException('User has no permission to modify this event');
        }

        // Get ticket count for validation
        $ticketsSold = $this->eventRepository->getTicketSalesStatistics($event)['total'] ?? 0;
        
        // Unpublish the event
        $this->eventDomainService->unpublishEvent($event, $ticketsSold);
        
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('event.' . $command->eventId);
        $this->cache->deletePattern('events.*');

        $this->logger->info('Event unpublished successfully', [
            'event_id' => $command->eventId
        ]);
    }
}
