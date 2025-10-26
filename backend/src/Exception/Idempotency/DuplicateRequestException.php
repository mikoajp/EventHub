<?php

namespace App\Exception\Idempotency;

class DuplicateRequestException extends IdempotencyException
{
    public function __construct(string $message = 'Duplicate request detected. Please wait for the original request to complete.')
    {
        parent::__construct($message, 409); // Conflict
    }
}
