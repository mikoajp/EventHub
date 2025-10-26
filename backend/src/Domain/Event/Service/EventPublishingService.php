<?php

namespace App\Domain\Event\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Exception\Authorization\InsufficientPermissionsException;
use App\Exception\Event\EventAlreadyPublishedException;
use App\Exception\Event\EventNotPublishableException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class EventPublishingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function publishEvent(Event $event, User $publisher): \DateTimeImmutable
    {
        if ($event->getStatus() === Event::STATUS_PUBLISHED) {
            throw new EventAlreadyPublishedException($event->getId()->toString());
        }

        if ($event->getStatus() !== Event::STATUS_DRAFT) {
            throw new EventNotPublishableException(
                $event->getId()->toString(),
                'Only draft events can be published. Current status: ' . $event->getStatus()
            );
        }

        if (!$this->canUserPublishEvent($event, $publisher)) {
            throw new InsufficientPermissionsException('publish this event');
        }

        $publishedAt = new \DateTimeImmutable();
        
        $event->setStatus(Event::STATUS_PUBLISHED);
        $event->setPublishedAt($publishedAt);

        $this->entityManager->flush();

        $this->logger->info('Event published successfully', [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'publisher_id' => $publisher->getId()->toString(),
            'published_at' => $publishedAt->format('Y-m-d H:i:s')
        ]);

        return $publishedAt;
    }

    public function canUserPublishEvent(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || 
               in_array('ROLE_ADMIN', $user->getRoles());
    }

    public function cancelEvent(Event $event, User $canceller, string $reason): void
    {
        if (!$this->canUserPublishEvent($event, $canceller)) {
            throw new InsufficientPermissionsException('cancel this event');
        }

        $event->setStatus(Event::STATUS_CANCELLED);
        $event->setCancelledAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Event cancelled', [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'canceller_id' => $canceller->getId()->toString(),
            'reason' => $reason
        ]);
    }
}