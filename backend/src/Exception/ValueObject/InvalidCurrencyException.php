<?php

namespace App\Exception\ValueObject;

class InvalidCurrencyException extends ValueObjectException
{
    public function __construct(string $currency = '')
    {
        $message = $currency === '' 
            ? 'Currency is required' 
            : sprintf('Invalid or empty currency: "%s"', $currency);
            
        parent::__construct($message, 400);
    }
}
