<?php

namespace App\MessageHandler\Command\Event;

use App\Entity\Event;
use App\Message\Command\Event\PublishEventCommand;
use App\Message\Event\EventPublishedEvent;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Application\Service\EventApplicationService;
use App\Application\Service\NotificationApplicationService;
use Doctrine\DBAL\Driver\PDO\PDOException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final readonly class PublishEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus,
        private EventApplicationService $eventApplicationService,
        private NotificationApplicationService $notificationApplicationService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(PublishEventCommand $command): void
    {
        try {
            $this->logger->info('Processing publish event command', [
                'event_id' => $command->eventId,
                'user_id' => $command->userId
            ]);

            $event = $this->eventRepository->find(Uuid::fromString($command->eventId));
            if (!$event) {
                $this->logger->error('Event not found', [
                    'event_id' => $command->eventId
                ]);
                return;
            }

            $user = $this->userRepository->find(Uuid::fromString($command->userId));
            if (!$user) {
                $this->logger->error('User not found', [
                    'user_id' => $command->userId
                ]);
                return;
            }
            if ($event->getStatus() === Event::STATUS_PUBLISHED) {
                $this->logger->info('Event already published - completing successfully', [
                    'event_id' => $command->eventId,
                    'event_name' => $event->getName(),
                    'published_at' => $event->getPublishedAt()?->format('Y-m-d H:i:s')
                ]);

                $this->sendSuccessNotifications($event, $user);

                // Notify all subscribers about the published event
                $this->notificationApplicationService->sendEventPublishedNotifications($event);

                if ($event->getPublishedAt()) {
                    $this->eventBus->dispatch(new EventPublishedEvent(
                        $event->getId()->toString(),
                        $user->getId()->toString(),
                        new \DateTimeImmutable('now')
                    ));
                }

                return;
            }

            if ($event->getStatus() === Event::STATUS_CANCELLED) {
                $this->logger->warning('Cannot publish cancelled event - completing gracefully', [
                    'event_id' => $command->eventId,
                    'status' => $event->getStatus()
                ]);
                return;
            }

            if ($event->getStatus() !== Event::STATUS_DRAFT) {
                $this->logger->warning('Event is not in draft status - completing gracefully', [
                    'event_id' => $command->eventId,
                    'current_status' => $event->getStatus(),
                    'expected_status' => Event::STATUS_DRAFT
                ]);
                return;
            }

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->logger->error('User has no permission to publish event - completing gracefully', [
                    'event_id' => $command->eventId,
                    'user_id' => $command->userId,
                    'organizer_id' => $event->getOrganizer()->getId()->toString()
                ]);
                return;
            }

            $publishedAt = $this->publishEvent($event);

            $this->sendSuccessNotifications($event, $user);

            // Notify all subscribers about the published event
            $this->notificationApplicationService->sendEventPublishedNotifications($event);

            $this->eventBus->dispatch(new EventPublishedEvent(
                $event->getId()->toString(),
                $user->getId()->toString(),
                $publishedAt
            ));

        } catch (\Exception $e) {
            if ($this->isCriticalException($e)) {
                $this->logger->error('Critical database/network error in publish event handler', [
                    'event_id' => $command->eventId,
                    'user_id' => $command->userId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }

            $this->logger->warning('Publish event command completed with warning', [
                'event_id' => $command->eventId,
                'reason' => $e->getMessage()
            ]);
        }
    }

    private function publishEvent(Event $event): \DateTimeImmutable
    {
        $wasInTransaction = $this->entityManager->getConnection()->isTransactionActive();

        if (!$wasInTransaction) {
            $this->entityManager->beginTransaction();
        }

        try {
            $publishedAt = new \DateTimeImmutable();
            $event->setStatus(Event::STATUS_PUBLISHED);
            $event->setPublishedAt($publishedAt);

            $this->entityManager->flush();

            if (!$wasInTransaction) {
                $this->entityManager->commit();
            }

            $this->logger->info('Event published successfully', [
                'event_id' => $event->getId()->toString(),
                'event_name' => $event->getName(),
                'published_at' => $publishedAt->format('Y-m-d H:i:s')
            ]);

            return $publishedAt;

        } catch (\Exception $e) {
            if (!$wasInTransaction && $this->entityManager->getConnection()->isTransactionActive()) {
                try {
                    $this->entityManager->rollback();
                } catch (\Exception $rollbackException) {
                    $this->logger->error('Failed to rollback transaction', [
                        'original_error' => $e->getMessage(),
                        'rollback_error' => $rollbackException->getMessage()
                    ]);
                }
            }

            $this->logger->error('Database error during event publish', [
                'event_id' => $event->getId()->toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function sendSuccessNotifications(Event $event, $user): void
    {
        try {
            $this->notificationApplicationService->sendNotificationToUser($user->getId()->toString(), [
                'title' => 'Event Published Successfully!',
                'message' => "Your event '{$event->getName()}' has been published and is now visible to users.",
                'type' => 'success',
                'event_id' => $event->getId()->toString(),
                'timestamp' => $event->getPublishedAt()->format('c')
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send success notifications', [
                'event_id' => $event->getId()->toString(),
                'user_id' => $user->getId()->toString(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function isCriticalException(\Exception $e): bool
    {
        return $e instanceof \Doctrine\DBAL\Exception ||
            $e instanceof PDOException ||
            str_contains($e->getMessage(), 'connection') ||
            str_contains($e->getMessage(), 'transaction');
    }
}