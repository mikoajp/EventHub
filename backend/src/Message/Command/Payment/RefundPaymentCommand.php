<?php

namespace App\Message\Command\Payment;

/**
 * Command to refund a payment (compensation)
 */
final readonly class RefundPaymentCommand
{
    public function __construct(
        public string $ticketId,
        public string $paymentId,
        public int $amount,
        public string $reason
    ) {}
}
