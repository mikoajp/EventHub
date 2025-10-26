<?php

namespace App\Exception\Ticket;

use App\Exception\DomainException;

/**
 * Base exception for ticket-related domain errors.
 */
abstract class TicketException extends DomainException
{
    protected function __construct(string $message, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
