<?php

namespace App\Exception\Ticket;

/**
 * Thrown when trying to transfer a ticket that cannot be transferred.
 */
final class TicketNotTransferableException extends TicketException
{
    protected string $errorCode = 'TICKET_NOT_TRANSFERABLE';

    public function __construct(string $ticketId, string $reason)
    {
        parent::__construct(
            sprintf('Ticket "%s" cannot be transferred: %s', $ticketId, $reason),
            ['ticket_id' => $ticketId, 'reason' => $reason]
        );
    }
}
