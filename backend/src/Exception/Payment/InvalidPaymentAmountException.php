<?php

namespace App\Exception\Payment;

/**
 * Thrown when payment amount is invalid.
 */
final class InvalidPaymentAmountException extends PaymentException
{
    protected string $errorCode = 'INVALID_PAYMENT_AMOUNT';

    public function __construct(float $amount, string $reason)
    {
        parent::__construct(
            sprintf('Invalid payment amount %.2f: %s', $amount, $reason),
            ['amount' => $amount, 'reason' => $reason]
        );
    }
}
