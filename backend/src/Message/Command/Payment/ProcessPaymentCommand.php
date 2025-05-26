<?php

namespace App\Message\Command\Payment;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public string $ticketId,
        public string $paymentMethodId,
        public int $amount,
        public string $currency = 'USD'
    ) {}
}
