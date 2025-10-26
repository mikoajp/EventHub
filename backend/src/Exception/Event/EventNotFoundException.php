<?php

namespace App\Exception\Event;

/**
 * Thrown when an event cannot be found.
 */
final class EventNotFoundException extends EventException
{
    protected string $errorCode = 'EVENT_NOT_FOUND';

    public function __construct(string $eventId)
    {
        parent::__construct(
            sprintf('Event with ID "%s" not found', $eventId),
            ['event_id' => $eventId]
        );
    }
}
