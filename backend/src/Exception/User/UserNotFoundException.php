<?php

namespace App\Exception\User;

/**
 * Thrown when a user cannot be found.
 */
final class UserNotFoundException extends UserException
{
    protected string $errorCode = 'USER_NOT_FOUND';

    public function __construct(string $userId)
    {
        parent::__construct(
            sprintf('User with ID "%s" not found', $userId),
            ['user_id' => $userId]
        );
    }
}
