<?php

namespace App\Exception\Payment;

/**
 * Thrown when trying to refund a non-refundable payment.
 */
final class PaymentNotRefundableException extends PaymentException
{
    protected string $errorCode = 'PAYMENT_NOT_REFUNDABLE';

    public function __construct(string $paymentId, string $reason)
    {
        parent::__construct(
            sprintf('Payment "%s" is not refundable: %s', $paymentId, $reason),
            ['payment_id' => $paymentId, 'reason' => $reason]
        );
    }
}
