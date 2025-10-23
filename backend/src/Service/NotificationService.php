<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\UserRepository;
use App\Infrastructure\RealTime\MercurePublisher;

final readonly class NotificationService
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private MercurePublisher $mercurePublisher
    ) {}

    public function notifyEventPublished(Event $event): void
    {
        error_log("NotificationService::notifyEventPublished called for event: " . $event->getName());
        
        $subscribers = $this->userRepository->findAll();
        error_log("Found " . count($subscribers) . " subscribers to notify");

        // Send email notifications
        foreach ($subscribers as $subscriber) {
            $this->emailService->sendEventPublishedNotification($event, $subscriber);
        }

        // Publish event to Mercure for real-time updates
        $eventData = [
            'type' => 'event_published',
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
            'venue' => $event->getVenue(),
            'message' => "New event published: {$event->getName()}",
            'timestamp' => (new \DateTime())->format('c')
        ];

        error_log("Publishing event data to Mercure: " . json_encode($eventData));
        $this->mercurePublisher->publishEvent($eventData);

        // Send notifications to all subscribers
        foreach ($subscribers as $subscriber) {
            $notificationData = [
                'title' => 'New Event Available',
                'message' => "Check out the new event: {$event->getName()}",
                'type' => 'info',
                'event_id' => $event->getId()->toString()
            ];
            
            error_log("Publishing notification to user {$subscriber->getId()->toString()}: " . json_encode($notificationData));
            $this->mercurePublisher->publishNotification($notificationData, $subscriber->getId()->toString());
        }
        
        error_log("NotificationService::notifyEventPublished completed");
    }

    public function shareOnSocialMedia(Event $event): void
    {
        $message = "New event: {$event->getName()} at {$event->getVenue()} on {$event->getEventDate()->format('M j, Y')}";

        error_log("Social Media Post: {$message}");

        $socialData = [
            'event_id' => $event->getId()->toString(),
            'message' => $message,
            'platform' => 'general',
            'timestamp' => (new \DateTime())->format('c')
        ];

        $this->mercurePublisher->publishSocial($socialData);

        $this->mercurePublisher->publishNotification([
            'title' => 'Event Shared',
            'message' => "Event '{$event->getName()}' has been shared on social media",
            'type' => 'success'
        ]);
    }

    public function sendRealTimeUpdate(string $topic, array $data): void
    {
        error_log("Sending real-time update to topic: {$topic}");
        $this->mercurePublisher->publish($topic, $data);
    }

    public function publishNotificationToUser(string $userId, array $notificationData): void
    {
        error_log("Publishing notification to user {$userId}: " . json_encode($notificationData));
        $this->mercurePublisher->publishNotification($notificationData, $userId);
    }

    public function publishGlobalNotification(array $notificationData): void
    {
        error_log("Publishing global notification: " . json_encode($notificationData));
        $this->mercurePublisher->publishNotification($notificationData);
    }

    public function notifyEventUpdated(Event $event): void
    {
        $eventData = [
            'type' => 'event_updated',
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'message' => "Event '{$event->getName()}' has been updated",
            'timestamp' => (new \DateTime())->format('c')
        ];

        error_log("Publishing event updated to Mercure: " . json_encode($eventData));
        $this->mercurePublisher->publishEvent($eventData);
    }

    public function notifyEventCancelled(Event $event): void
    {
        $attendees = $event->getAttendees();

        // Send email notifications
        foreach ($attendees as $attendee) {
            $this->emailService->sendEventCancelledNotification($event, $attendee);
        }

        // Publish to Mercure
        $eventData = [
            'type' => 'event_cancelled',
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'message' => "Event '{$event->getName()}' has been cancelled",
            'timestamp' => (new \DateTime())->format('c')
        ];

        error_log("Publishing event cancelled to Mercure: " . json_encode($eventData));
        $this->mercurePublisher->publishEvent($eventData);

        // Send individual notifications to attendees
        foreach ($attendees as $attendee) {
            $this->mercurePublisher->publishNotification([
                'title' => 'Event Cancelled',
                'message' => "Unfortunately, the event '{$event->getName()}' has been cancelled",
                'type' => 'error',
                'event_id' => $event->getId()->toString()
            ], $attendee->getId()->toString());
        }
    }
}