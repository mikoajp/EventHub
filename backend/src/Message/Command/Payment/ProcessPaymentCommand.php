<?php

namespace App\Message\Command\Payment;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public string $ticketId,
        public string $paymentMethodId,
        public int $amount,
        public string $currency = 'USD',
        public ?string $idempotencyKey = null
    ) {}

    public function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }

        // Generate deterministic key for payment
        return hash('sha256', implode('|', [
            'payment',
            $this->ticketId,
            $this->paymentMethodId,
            $this->amount,
            $this->currency
        ]));
    }
}
