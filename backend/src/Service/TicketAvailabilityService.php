<?php

namespace App\Service;

use App\Entity\TicketType;

final class TicketAvailabilityService
{
    public function isAvailable(TicketType $ticketType, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }
        return $ticketType->getQuantity() >= $quantity;
    }
}
