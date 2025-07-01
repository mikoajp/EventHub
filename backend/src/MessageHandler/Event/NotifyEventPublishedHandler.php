<?php

namespace App\MessageHandler\Event;

use App\Message\Event\EventPublishedEvent;
use App\Repository\EventRepository;
use App\Application\Service\NotificationApplicationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class NotifyEventPublishedHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private NotificationApplicationService $notificationApplicationService
    ) {}

    public function __invoke(EventPublishedEvent $event): void
    {
        $eventEntity = $this->eventRepository->find(Uuid::fromString($event->eventId));
        
        if (!$eventEntity) {
            return;
        }

        // Send to social media, etc.
        $this->notificationApplicationService->shareEventOnSocialMedia($eventEntity);
    }
}