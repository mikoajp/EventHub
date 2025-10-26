<?php

namespace App\Exception\Ticket;

/**
 * Thrown when a ticket reservation is invalid.
 */
final class InvalidTicketReservationException extends TicketException
{
    protected string $errorCode = 'INVALID_TICKET_RESERVATION';

    public function __construct(string $message, array $context = [])
    {
        parent::__construct($message, $context);
    }
}
