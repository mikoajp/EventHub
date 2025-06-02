<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMQConnection
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private array $declaredExchanges = [];

    public function __construct(
        private string $host = 'rabbitmq',
        private int $port = 5672,
        private string $user = 'eventhub',
        private string $password = 'secret'
    ) {}

    /**
     * @throws \Exception
     */
    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password
            );
        }

        return $this->connection;
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->channel = $this->getConnection()->channel();
        }

        return $this->channel;
    }

    private function declareExchange(string $exchange, string $type = 'topic'): void
    {
        if (!in_array($exchange, $this->declaredExchanges)) {
            $this->getChannel()->exchange_declare($exchange, $type, false, true, false);
            $this->declaredExchanges[] = $exchange;
        }
    }

    public function publish(string $exchange, string $routingKey, array $data): bool
    {
        try {
            $this->declareExchange($exchange);

            $message = new AMQPMessage(
                json_encode($data),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'timestamp' => time()
                ]
            );

            $this->getChannel()->basic_publish($message, $exchange, $routingKey);
            return true;

        } catch (\Exception $e) {
            error_log("Failed to publish message to exchange '{$exchange}' with routing key '{$routingKey}': " . $e->getMessage());
            return false;
        }
    }

    public function publishEvent(array $eventData): bool
    {
        return $this->publish('events', 'published', $eventData);
    }

    public function publishNotification(array $notificationData, ?string $userId = null): bool
    {
        $routingKey = $userId ? "user.{$userId}" : 'global';
        return $this->publish('notifications', $routingKey, $notificationData);
    }

    public function publishSocial(array $socialData, string $platform = 'general'): bool
    {
        return $this->publish('social', "post.{$platform}", $socialData);
    }

    public function close(): void
    {
        try {
            if ($this->channel && $this->channel->is_open()) {
                $this->channel->close();
            }
            if ($this->connection && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            error_log("Error closing RabbitMQ connection: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}