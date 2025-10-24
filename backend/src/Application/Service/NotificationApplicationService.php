<?php

namespace App\Application\Service;

use App\Entity\Event;
use App\Infrastructure\Messaging\MessageBusInterface;
use App\Infrastructure\Email\EmailServiceInterface;
use App\Repository\UserRepository;

final readonly class NotificationApplicationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EmailServiceInterface $emailService,
        private UserRepository $userRepository
    ) {}

    public function sendEventPublishedNotifications(Event $event): void
    {
        $subscribers = $this->userRepository->findAll();

        // Send email notifications
        foreach ($subscribers as $subscriber) {
            $this->emailService->sendEventPublishedNotification($event, $subscriber);
        }

        // Send real-time notifications
        $eventData = [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
            'venue' => $event->getVenue(),
            'message' => "New event published: {$event->getName()}",
            'timestamp' => (new \DateTimeImmutable())->format('c')
        ];

        $this->messageBus->publishEvent($eventData);

        foreach ($subscribers as $subscriber) {
            $this->messageBus->publishNotification([
                'title' => 'New Event Available',
                'message' => "Check out the new event: {$event->getName()}",
                'type' => 'info',
                'event_id' => $event->getId()->toString()
            ], $subscriber->getId()->toString());
        }
    }

    public function sendEventCancelledNotifications(Event $event): void
    {
        $attendees = $event->getAttendees();

        // Send email notifications
        foreach ($attendees as $attendee) {
            $this->emailService->sendEventCancelledNotification($event, $attendee);
        }

        // Send real-time notifications
        $this->messageBus->publish('events', 'cancelled', [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'message' => "Event '{$event->getName()}' has been cancelled",
            'timestamp' => (new \DateTimeImmutable())->format('c')
        ]);

        foreach ($attendees as $attendee) {
            $this->messageBus->publishNotification([
                'title' => 'Event Cancelled',
                'message' => "Unfortunately, the event '{$event->getName()}' has been cancelled",
                'type' => 'error',
                'event_id' => $event->getId()->toString()
            ], $attendee->getId()->toString());
        }
    }

    public function shareEventOnSocialMedia(Event $event): void
    {
        $message = "New event: {$event->getName()} at {$event->getVenue()} on {$event->getEventDate()->format('M j, Y')}";

        $socialData = [
            'event_id' => $event->getId()->toString(),
            'message' => $message,
            'platform' => 'general',
            'timestamp' => (new \DateTimeImmutable())->format('c')
        ];

        $this->messageBus->publishSocial($socialData);

        $this->messageBus->publishNotification([
            'title' => 'Event Shared',
            'message' => "Event '{$event->getName()}' has been shared on social media",
            'type' => 'success'
        ]);
    }

    public function sendNotificationToUser(string $userId, array $notificationData): void
    {
        $this->messageBus->publishNotification($notificationData, $userId);
    }

    public function sendGlobalNotification(array $notificationData): void
    {
        $this->messageBus->publishNotification($notificationData);
    }
}