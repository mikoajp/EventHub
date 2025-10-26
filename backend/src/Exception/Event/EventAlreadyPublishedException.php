<?php

namespace App\Exception\Event;

/**
 * Thrown when trying to publish an already published event.
 */
final class EventAlreadyPublishedException extends EventException
{
    protected string $errorCode = 'EVENT_ALREADY_PUBLISHED';

    public function __construct(string $eventId)
    {
        parent::__construct(
            sprintf('Event "%s" is already published', $eventId),
            ['event_id' => $eventId]
        );
    }
}
