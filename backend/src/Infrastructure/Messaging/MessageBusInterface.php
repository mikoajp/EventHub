<?php

namespace App\Infrastructure\Messaging;

interface MessageBusInterface
{
    /**
     * Publish event data
     */
    public function publishEvent(array $eventData): bool;

    /**
     * Publish notification
     */
    public function publishNotification(array $notificationData, ?string $userId = null): bool;

    /**
     * Publish social media data
     */
    public function publishSocial(array $socialData, string $platform = 'general'): bool;

    /**
     * Publish to specific exchange and routing key
     */
    public function publish(string $exchange, string $routingKey, array $data): bool;
}