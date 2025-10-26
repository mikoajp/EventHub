<?php

namespace App\Exception\Event;

/**
 * Thrown when an event cannot be cancelled in its current state.
 */
final class EventCannotBeCancelledException extends EventException
{
    protected string $errorCode = 'EVENT_CANNOT_BE_CANCELLED';

    public function __construct(string $eventId, string $reason)
    {
        parent::__construct(
            sprintf('Event "%s" cannot be cancelled: %s', $eventId, $reason),
            ['event_id' => $eventId, 'reason' => $reason]
        );
    }
}
