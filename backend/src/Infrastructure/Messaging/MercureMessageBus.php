<?php

namespace App\Infrastructure\Messaging;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;

final readonly class MercureMessageBus implements MessageBusInterface
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {}

    public function publishEvent(array $eventData): void
    {
        try {
            $update = new Update(
                topics: 'events',
                data: json_encode([
                    'type' => 'event.published',
                    'data' => $eventData
                ])
            );

            $this->hub->publish($update);
            
            $this->logger->info('Published event to Mercure', [
                'event_id' => $eventData['event_id'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish event to Mercure', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function publishNotification(array $notificationData, ?string $userId = null): void
    {
        try {
            // If userId is provided, send private notification
            // Otherwise, send to public notifications topic
            $topic = $userId 
                ? "notifications/user/{$userId}" 
                : 'notifications';

            $update = new Update(
                topics: $topic,
                data: json_encode([
                    'type' => 'notification',
                    'data' => $notificationData,
                    'timestamp' => $notificationData['timestamp'] ?? (new \DateTimeImmutable())->format('c')
                ]),
                private: $userId !== null // Private if userId is set
            );

            $this->hub->publish($update);
            
            $this->logger->info('Published notification to Mercure', [
                'user_id' => $userId,
                'type' => $notificationData['type'] ?? 'info'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish notification to Mercure', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }
    }

    public function publish(string $channel, string $type, array $payload): void
    {
        try {
            $update = new Update(
                topics: $channel,
                data: json_encode([
                    'type' => $type,
                    'data' => $payload
                ])
            );

            $this->hub->publish($update);
            
            $this->logger->info('Published message to Mercure', [
                'channel' => $channel,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish message to Mercure', [
                'error' => $e->getMessage(),
                'channel' => $channel
            ]);
        }
    }

    public function publishSocial(array $socialData): void
    {
        try {
            $update = new Update(
                topics: 'social',
                data: json_encode([
                    'type' => 'social.share',
                    'data' => $socialData
                ])
            );

            $this->hub->publish($update);
            
            $this->logger->info('Published social event to Mercure', [
                'event_id' => $socialData['event_id'] ?? null,
                'platform' => $socialData['platform'] ?? 'general'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish social event to Mercure', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
