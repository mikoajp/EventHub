<?php

namespace App\Exception\Event;

use App\Exception\DomainException;

/**
 * Base exception for event-related domain errors.
 */
abstract class EventException extends DomainException
{
    protected function __construct(string $message, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
