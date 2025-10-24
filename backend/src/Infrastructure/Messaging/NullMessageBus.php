<?php

namespace App\Infrastructure\Messaging;

final class NullMessageBus implements MessageBusInterface
{
    public function publishEvent(array $eventData): void { }
    public function publishNotification(array $notificationData, ?string $userId = null): void { }
    public function publish(string $channel, string $type, array $payload): void { }
    public function publishSocial(array $socialData): void { }
}
