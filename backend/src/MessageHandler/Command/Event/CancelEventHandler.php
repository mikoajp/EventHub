<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Event;

use App\Message\Command\Event\CancelEventCommand;
use App\Repository\EventRepository;

use App\Domain\Event\Service\EventDomainService;
use App\Infrastructure\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class CancelEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventDomainService $eventDomainService,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CancelEventCommand $command): void
    {
        $this->logger->info('Processing cancel event command', [
            'event_id' => $command->eventId
        ]);

        $event = $this->eventRepository->find(Uuid::fromString($command->eventId));
        if (!$event) {
            throw new \App\Exception\Event\EventNotFoundException($command->eventId);
        }
        
        // Cancel the event
        $this->eventDomainService->cancelEvent($event);
        
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('event.' . $command->eventId);
        $this->cache->deletePattern('events.*');

        $this->logger->info('Event cancelled successfully', [
            'event_id' => $command->eventId
        ]);
    }
}




