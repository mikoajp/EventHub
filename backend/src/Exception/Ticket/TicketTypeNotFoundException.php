<?php

namespace App\Exception\Ticket;

/**
 * Thrown when a ticket type cannot be found.
 */
final class TicketTypeNotFoundException extends TicketException
{
    protected string $errorCode = 'TICKET_TYPE_NOT_FOUND';

    public function __construct(string $ticketTypeId)
    {
        parent::__construct(
            sprintf('Ticket type with ID "%s" not found', $ticketTypeId),
            ['ticket_type_id' => $ticketTypeId]
        );
    }
}
