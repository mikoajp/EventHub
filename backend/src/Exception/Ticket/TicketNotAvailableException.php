<?php

namespace App\Exception\Ticket;

/**
 * Thrown when trying to purchase a ticket that is not available.
 */
final class TicketNotAvailableException extends TicketException
{
    protected string $errorCode = 'TICKET_NOT_AVAILABLE';

    public function __construct(string $ticketTypeId, int $requested, int $available)
    {
        parent::__construct(
            sprintf('Not enough tickets available. Requested: %d, Available: %d', $requested, $available),
            [
                'ticket_type_id' => $ticketTypeId,
                'requested' => $requested,
                'available' => $available
            ]
        );
    }
}
