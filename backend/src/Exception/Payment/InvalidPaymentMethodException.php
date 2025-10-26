<?php

namespace App\Exception\Payment;

/**
 * Thrown when an invalid payment method is provided.
 */
final class InvalidPaymentMethodException extends PaymentException
{
    protected string $errorCode = 'INVALID_PAYMENT_METHOD';

    public function __construct(string $paymentMethod)
    {
        parent::__construct(
            sprintf('Invalid payment method: %s', $paymentMethod),
            ['payment_method' => $paymentMethod]
        );
    }
}
