<?php

namespace App\Exception\Event;

/**
 * Thrown when an event cannot be unpublished.
 */
final class EventCannotBeUnpublishedException extends EventException
{
    protected string $errorCode = 'EVENT_CANNOT_BE_UNPUBLISHED';

    public function __construct(string $eventId, int $ticketsSold)
    {
        parent::__construct(
            sprintf('Event "%s" cannot be unpublished - %d tickets already sold', $eventId, $ticketsSold),
            ['event_id' => $eventId, 'tickets_sold' => $ticketsSold]
        );
    }
}
