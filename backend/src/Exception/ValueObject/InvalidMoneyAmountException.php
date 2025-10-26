<?php

namespace App\Exception\ValueObject;

class InvalidMoneyAmountException extends ValueObjectException
{
    public function __construct(int $amount, string $reason = '')
    {
        $message = $reason !== '' 
            ? sprintf('Invalid money amount %d: %s', $amount, $reason)
            : sprintf('Invalid money amount: %d', $amount);
            
        parent::__construct($message, 400);
    }
}
