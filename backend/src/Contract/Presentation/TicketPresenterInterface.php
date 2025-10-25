<?php

namespace App\Contract\Presentation;

interface TicketPresenterInterface
{
    public function presentAvailability(array $availability): array;
    public function presentPurchase(array $result): array;
    public function presentUserTickets(array $tickets): array;
    public function presentCancel(?string $message = 'Ticket cancelled'): array;

    public function presentAvailabilityDto(array $availability): \App\DTO\TicketAvailabilityDTO;
    public function presentPurchaseDto(array $result): \App\DTO\TicketPurchaseResultDTO;
    public function presentUserTicketsDto(array $tickets): \App\DTO\UserTicketsDTO;
}
