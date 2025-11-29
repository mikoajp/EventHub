<?php

namespace App\MessageHandler\Command\Event;

use App\Message\Command\Event\UpdateEventCommand;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Domain\Event\Service\EventDomainService;
use App\Infrastructure\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class UpdateEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EventDomainService $eventDomainService,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(UpdateEventCommand $command): void
    {
        $this->logger->info('Processing update event command', [
            'event_id' => $command->eventId,
            'user_id' => $command->userId
        ]);

        $event = $this->eventRepository->findByUuid($command->eventId);
        if (!$event) {
            throw new \App\Exception\Event\EventNotFoundException($command->eventId);
        }

        $user = $this->userRepository->find(Uuid::fromString($command->userId));
        if (!$user) {
            throw new \App\Exception\User\UserNotFoundException($command->userId);
        }

        // Check permissions
        if (!$this->eventDomainService->canUserModifyEvent($event, $user)) {
            throw new \App\Exception\Authorization\InsufficientPermissionsException('modify this event');
        }

        // Update the event
        $this->eventDomainService->updateEvent($event, $command->eventDTO);
        
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('event.' . $command->eventId);
        $this->cache->deletePattern('events.*');

        $this->logger->info('Event updated successfully', [
            'event_id' => $command->eventId
        ]);
    }
}
