<?php

declare(strict_types=1);

namespace App\MessageHandler\Query\Ticket;

use App\Message\Query\Ticket\CheckTicketAvailabilityQuery;
use App\Application\Service\TicketApplicationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckTicketAvailabilityHandler
{
    public function __construct(private TicketApplicationService $ticketApplicationService) {}

    public function __invoke(CheckTicketAvailabilityQuery $query): array
    {
        return $this->ticketApplicationService->checkTicketAvailability($query->eventId, $query->ticketTypeId, $query->quantity);
    }
}



