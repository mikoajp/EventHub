<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\UserRepository;

final readonly class NotificationService
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private RabbitMQConnection $rabbitMQ
    ) {}

    public function notifyEventPublished(Event $event): void
    {
        $subscribers = $this->userRepository->findAll();

        foreach ($subscribers as $subscriber) {
            $this->emailService->sendEventPublishedNotification($event, $subscriber);
        }

        $eventData = [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
            'venue' => $event->getVenue(),
            'message' => "New event published: {$event->getName()}",
            'timestamp' => (new \DateTime())->format('c')
        ];

        $this->rabbitMQ->publishEvent($eventData);

        foreach ($subscribers as $subscriber) {
            $this->rabbitMQ->publishNotification([
                'title' => 'New Event Available',
                'message' => "Check out the new event: {$event->getName()}",
                'type' => 'info',
                'event_id' => $event->getId()->toString()
            ], $subscriber->getId()->toString());
        }
    }

    public function shareOnSocialMedia(Event $event): void
    {
        $message = "🎉 New event: {$event->getName()} at {$event->getVenue()} on {$event->getEventDate()->format('M j, Y')}";

        // Log the social media post (simulate posting)
        error_log("Social Media Post: {$message}");

        // Publish to social media exchange
        $socialData = [
            'event_id' => $event->getId()->toString(),
            'message' => $message,
            'platform' => 'general',
            'timestamp' => (new \DateTime())->format('c')
        ];

        $this->rabbitMQ->publishSocial($socialData);

        $this->rabbitMQ->publishNotification([
            'title' => 'Event Shared',
            'message' => "Event '{$event->getName()}' has been shared on social media",
            'type' => 'success'
        ]);
    }

    public function sendRealTimeUpdate(string $topic, array $data): void
    {
        $topicParts = explode('/', $topic);
        $exchange = $topicParts[0] ?? 'notifications';
        $routingKey = isset($topicParts[1]) ? $topicParts[1] : 'general';

        $this->rabbitMQ->publish($exchange, $routingKey, $data);
    }

    public function publishNotificationToUser(string $userId, array $notificationData): void
    {
        $this->rabbitMQ->publishNotification($notificationData, $userId);
    }

    public function publishGlobalNotification(array $notificationData): void
    {
        $this->rabbitMQ->publishNotification($notificationData);
    }

    public function notifyEventUpdated(Event $event): void
    {
        $this->rabbitMQ->publish('events', 'updated', [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'message' => "Event '{$event->getName()}' has been updated",
            'timestamp' => (new \DateTime())->format('c')
        ]);
    }

    public function notifyEventCancelled(Event $event): void
    {
        $attendees = $event->getAttendees();


        foreach ($attendees as $attendee) {
            $this->emailService->sendEventCancelledNotification($event, $attendee);
        }

        $this->rabbitMQ->publish('events', 'cancelled', [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'message' => "Event '{$event->getName()}' has been cancelled",
            'timestamp' => (new \DateTime())->format('c')
        ]);

        foreach ($attendees as $attendee) {
            $this->rabbitMQ->publishNotification([
                'title' => 'Event Cancelled',
                'message' => "Unfortunately, the event '{$event->getName()}' has been cancelled",
                'type' => 'error',
                'event_id' => $event->getId()->toString()
            ], $attendee->getId()->toString());
        }
    }
}