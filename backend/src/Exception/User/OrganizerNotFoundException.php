<?php

namespace App\Exception\User;

/**
 * Thrown when an organizer (user) cannot be found.
 */
final class OrganizerNotFoundException extends UserException
{
    protected string $errorCode = 'ORGANIZER_NOT_FOUND';

    public function __construct(string $organizerId)
    {
        parent::__construct(
            sprintf('Organizer with ID "%s" not found', $organizerId),
            ['organizer_id' => $organizerId]
        );
    }
}
