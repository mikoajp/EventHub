<?php

namespace App\Infrastructure\Messaging;

use App\Infrastructure\RealTime\MercurePublisher;

final readonly class MercureAdapter implements MessageBusInterface
{
    public function __construct(
        private MercurePublisher $mercurePublisher
    ) {}

    public function publishEvent(array $eventData): bool
    {
        try {
            error_log("MercureAdapter::publishEvent called with data: " . json_encode($eventData));
            
            // Ensure type is set
            if (!isset($eventData['type'])) {
                $eventData['type'] = 'event_published';
            }
            
            $this->mercurePublisher->publishEvent($eventData);
            error_log("MercureAdapter::publishEvent: SUCCESS");
            return true;
        } catch (\Exception $e) {
            error_log("MercureAdapter::publishEvent FAILED: " . $e->getMessage());
            return false;
        }
    }

    public function publishNotification(array $notificationData, ?string $userId = null): bool
    {
        try {
            $routingKey = $userId ? "user.{$userId}" : 'global';
            error_log("MercureAdapter::publishNotification called with routing key '{$routingKey}' and data: " . json_encode($notificationData));
            
            $this->mercurePublisher->publishNotification($notificationData, $userId);
            error_log("MercureAdapter::publishNotification: SUCCESS");
            return true;
        } catch (\Exception $e) {
            error_log("MercureAdapter::publishNotification FAILED: " . $e->getMessage());
            return false;
        }
    }

    public function publishSocial(array $socialData, string $platform = 'general'): bool
    {
        try {
            error_log("MercureAdapter::publishSocial called with platform '{$platform}' and data: " . json_encode($socialData));
            
            $this->mercurePublisher->publishSocial($socialData);
            error_log("MercureAdapter::publishSocial: SUCCESS");
            return true;
        } catch (\Exception $e) {
            error_log("MercureAdapter::publishSocial FAILED: " . $e->getMessage());
            return false;
        }
    }

    public function publish(string $exchange, string $routingKey, array $data): bool
    {
        try {
            error_log("MercureAdapter::publish called with exchange '{$exchange}', routing key '{$routingKey}' and data: " . json_encode($data));
            
            // Map exchange/routing to appropriate Mercure method
            switch ($exchange) {
                case 'events':
                    if (!isset($data['type'])) {
                        $data['type'] = 'event_' . $routingKey;
                    }
                    $this->mercurePublisher->publishEvent($data);
                    break;
                    
                case 'notifications':
                    $userId = str_starts_with($routingKey, 'user.') 
                        ? substr($routingKey, 5) 
                        : null;
                    $this->mercurePublisher->publishNotification($data, $userId);
                    break;
                    
                case 'social':
                    $this->mercurePublisher->publishSocial($data);
                    break;
                    
                default:
                    // Generic publish for other exchanges
                    $this->mercurePublisher->publish($exchange, $data);
            }
            
            error_log("MercureAdapter::publish: SUCCESS");
            return true;
        } catch (\Exception $e) {
            error_log("MercureAdapter::publish FAILED: " . $e->getMessage());
            return false;
        }
    }
}
