<?php

namespace App\MessageHandler\Event;

use App\Message\Event\EventPublishedEvent;
use App\Repository\EventRepository;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class NotifyEventPublishedHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private NotificationService $notificationService
    ) {}

    public function __invoke(EventPublishedEvent $event): void
    {
        $eventEntity = $this->eventRepository->find(Uuid::fromString($event->eventId));
        
        if (!$eventEntity) {
            return;
        }

        // Notify subscribers about new event
        $this->notificationService->notifyEventPublished($eventEntity);
        
        // Send to social media, etc.
        $this->notificationService->shareOnSocialMedia($eventEntity);
    }
}