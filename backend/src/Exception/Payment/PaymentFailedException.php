<?php

namespace App\Exception\Payment;

/**
 * Thrown when a payment fails.
 */
final class PaymentFailedException extends PaymentException
{
    protected string $errorCode = 'PAYMENT_FAILED';

    public function __construct(string $reason, ?string $paymentMethodId = null)
    {
        parent::__construct(
            sprintf('Payment failed: %s', $reason),
            [
                'reason' => $reason,
                'payment_method_id' => $paymentMethodId
            ]
        );
    }
}
