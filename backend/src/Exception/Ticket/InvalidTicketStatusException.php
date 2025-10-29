<?php

namespace App\Exception\Ticket;

/**
 * Thrown when a ticket is in an invalid status for the requested operation.
 */
final class InvalidTicketStatusException extends TicketException
{
    protected string $errorCode = 'INVALID_TICKET_STATUS';

    public function __construct(
        string $ticketId,
        \App\Enum\TicketStatus|string $currentStatus,
        \App\Enum\TicketStatus|string $expectedStatus
    ) {
        // Convert enum to string if needed
        if ($currentStatus instanceof \App\Enum\TicketStatus) {
            $currentStatus = $currentStatus->value;
        }
        if ($expectedStatus instanceof \App\Enum\TicketStatus) {
            $expectedStatus = $expectedStatus->value;
        }
        
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
