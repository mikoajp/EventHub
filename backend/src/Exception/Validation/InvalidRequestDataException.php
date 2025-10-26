<?php

namespace App\Exception\Validation;

use App\Exception\ApplicationException;

/**
 * Thrown when request data is invalid or malformed.
 */
final class InvalidRequestDataException extends ApplicationException
{
    protected string $errorCode = 'INVALID_REQUEST_DATA';

    public function __construct(string $message, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
