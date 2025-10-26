<?php

namespace App\Exception\Validation;

use App\Exception\ApplicationException;

/**
 * Thrown when JSON data is invalid or malformed.
 */
final class InvalidJsonException extends ApplicationException
{
    protected string $errorCode = 'INVALID_JSON';

    public function __construct(string $details = '')
    {
        $message = 'Invalid JSON';
        if ($details) {
            $message .= ': ' . $details;
        }
        
        parent::__construct($message, 0, null, ['details' => $details]);
    }
}
