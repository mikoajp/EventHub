<?php

namespace App\Exception\Ticket;

/**
 * Thrown when a ticket is in an invalid status for the requested operation.
 */
final class InvalidTicketStatusException extends TicketException
{
    protected string $errorCode = 'INVALID_TICKET_STATUS';

    public function __construct(string $ticketId, string $currentStatus, string $expectedStatus)
    {
        parent::__construct(
            sprintf(
                'Ticket "%s" has invalid status. Expected: %s, Current: %s',
                $ticketId,
                $expectedStatus,
                $currentStatus
            ),
            [
                'ticket_id' => $ticketId,
                'current_status' => $currentStatus,
                'expected_status' => $expectedStatus
            ]
        );
    }
}
