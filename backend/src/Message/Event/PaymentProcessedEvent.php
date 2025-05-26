<?php

namespace App\Message\Event;

final readonly class PaymentProcessedEvent
{
    public function __construct(
        public string $paymentId,
        public string $ticketId,
        public int $amount,
        public string $status,
        public \DateTimeImmutable $occurredAt
    ) {}
}
