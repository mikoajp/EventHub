<?php

namespace App\Exception\Validation;

use App\Exception\DomainException;

/**
 * Thrown when an email address is invalid.
 */
final class InvalidEmailException extends DomainException
{
    protected string $errorCode = 'INVALID_EMAIL';

    public function __construct(string $email)
    {
        parent::__construct(
            sprintf('Invalid email address: %s', $email),
            0,
            null,
            ['email' => $email]
        );
    }
}
