<?php

namespace App\Exception\Event;

/**
 * Thrown when attempting an operation that requires a published event.
 */
final class EventNotPublishedException extends EventException
{
    protected string $errorCode = 'EVENT_NOT_PUBLISHED';

    public function __construct(string $eventId)
    {
        parent::__construct(
            sprintf('Event "%s" is not published', $eventId),
            ['event_id' => $eventId]
        );
    }
}
