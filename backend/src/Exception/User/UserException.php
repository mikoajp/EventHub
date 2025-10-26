<?php

namespace App\Exception\User;

use App\Exception\DomainException;

/**
 * Base exception for user-related domain errors.
 */
abstract class UserException extends DomainException
{
    protected function __construct(string $message, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
