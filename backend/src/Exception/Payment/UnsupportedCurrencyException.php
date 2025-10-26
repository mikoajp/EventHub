<?php

namespace App\Exception\Payment;

/**
 * Thrown when an unsupported currency is used.
 */
final class UnsupportedCurrencyException extends PaymentException
{
    protected string $errorCode = 'UNSUPPORTED_CURRENCY';

    public function __construct(string $currency, array $supportedCurrencies = [])
    {
        $message = sprintf('Unsupported currency: %s', $currency);
        if (!empty($supportedCurrencies)) {
            $message .= sprintf('. Supported: %s', implode(', ', $supportedCurrencies));
        }
        
        parent::__construct($message, [
            'currency' => $currency,
            'supported_currencies' => $supportedCurrencies
        ]);
    }
}
