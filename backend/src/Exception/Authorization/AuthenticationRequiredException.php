<?php

namespace App\Exception\Authorization;

use App\Exception\ApplicationException;

/**
 * Thrown when authentication is required but user is not authenticated.
 */
final class AuthenticationRequiredException extends ApplicationException
{
    protected string $errorCode = 'AUTHENTICATION_REQUIRED';

    public function __construct(string $action = 'perform this action')
    {
        parent::__construct(
            sprintf('User must be authenticated to %s', $action),
            0,
            null,
            ['action' => $action]
        );
    }
}
