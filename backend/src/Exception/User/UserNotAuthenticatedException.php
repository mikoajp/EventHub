<?php

namespace App\Exception\User;

use App\Exception\ApplicationException;

/**
 * Thrown when a user must be authenticated but is not.
 */
final class UserNotAuthenticatedException extends ApplicationException
{
    protected string $errorCode = 'USER_NOT_AUTHENTICATED';

    public function __construct()
    {
        parent::__construct('User must be authenticated to perform this action');
    }
}
