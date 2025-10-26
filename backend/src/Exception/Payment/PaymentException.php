<?php

namespace App\Exception\Payment;

use App\Exception\DomainException;

/**
 * Base exception for payment-related domain errors.
 */
abstract class PaymentException extends DomainException
{
    protected function __construct(string $message, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
