<?php

namespace App\Exception\Event;

/**
 * Thrown when trying to publish an event that cannot be published.
 */
final class EventNotPublishableException extends EventException
{
    protected string $errorCode = 'EVENT_NOT_PUBLISHABLE';

    public function __construct(string $eventId, string $currentStatus)
    {
        parent::__construct(
            sprintf('Event "%s" cannot be published. Current status: %s', $eventId, $currentStatus),
            ['event_id' => $eventId, 'current_status' => $currentStatus]
        );
    }
}
