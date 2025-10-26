<?php

namespace App\Exception\Ticket;

/**
 * Thrown when a ticket cannot be found.
 */
final class TicketNotFoundException extends TicketException
{
    protected string $errorCode = 'TICKET_NOT_FOUND';

    public function __construct(string $ticketId)
    {
        parent::__construct(
            sprintf('Ticket with ID "%s" not found', $ticketId),
            ['ticket_id' => $ticketId]
        );
    }
}
