<?php

namespace App\Infrastructure\RealTime;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MercurePublisher
{
    public function __construct(
        private HubInterface $hub
    ) {}

    /**
     * Publish an event update to subscribers
     */
    public function publishEvent(array $eventData): void
    {
        $update = new Update(
            topic: 'https://eventhub.local/events',
            data: json_encode([
                'type' => $eventData['type'] ?? 'event_published',
                'event_id' => $eventData['event_id'] ?? null,
                'event_name' => $eventData['event_name'] ?? null,
                'event_date' => $eventData['event_date'] ?? null,
                'venue' => $eventData['venue'] ?? null,
                'message' => $eventData['message'] ?? null,
                'timestamp' => $eventData['timestamp'] ?? (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish a notification to all subscribers or specific user
     */
    public function publishNotification(array $notificationData, ?string $userId = null): void
    {
        $topic = $userId 
            ? "https://eventhub.local/notifications/user/{$userId}"
            : 'https://eventhub.local/notifications';

        $update = new Update(
            topic: $topic,
            data: json_encode([
                'type' => 'notification',
                'title' => $notificationData['title'] ?? 'Notification',
                'message' => $notificationData['message'] ?? '',
                'notificationType' => $notificationData['type'] ?? 'info',
                'event_id' => $notificationData['event_id'] ?? null,
                'timestamp' => (new \DateTime())->format('c')
            ]),
            private: $userId !== null // Make it private if user-specific
        );

        $this->hub->publish($update);
    }

    /**
     * Publish social media update
     */
    public function publishSocial(array $socialData): void
    {
        $update = new Update(
            topic: 'https://eventhub.local/social',
            data: json_encode([
                'type' => 'social_post',
                'event_id' => $socialData['event_id'] ?? null,
                'message' => $socialData['message'] ?? '',
                'platform' => $socialData['platform'] ?? 'general',
                'timestamp' => $socialData['timestamp'] ?? (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish ticket purchase notification
     */
    public function publishTicketPurchase(array $ticketData, string $userId): void
    {
        $update = new Update(
            topic: "https://eventhub.local/tickets",
            data: json_encode([
                'type' => 'ticket_purchased',
                'ticket_id' => $ticketData['ticket_id'] ?? null,
                'event_id' => $ticketData['event_id'] ?? null,
                'message' => $ticketData['message'] ?? 'Ticket purchased successfully',
                'timestamp' => (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);

        // Also send a personal notification
        $this->publishNotification([
            'title' => 'Ticket Purchased',
            'message' => $ticketData['message'] ?? 'Your ticket has been purchased successfully',
            'type' => 'success'
        ], $userId);
    }

    /**
     * Generic publish method for custom topics
     */
    public function publish(string $topic, array $data, bool $private = false): void
    {
        $update = new Update(
            topic: "https://eventhub.local/{$topic}",
            data: json_encode($data),
            private: $private
        );

        $this->hub->publish($update);
    }
}
