<?php

namespace App\DTO;

final readonly class TicketPurchaseResultDTO
{
    /**
     * @param array<int, array{id:string, ticketType:string, price:int}> $items
     */
    public function __construct(
        public string $orderId,
        public string $paymentId,
        public int $total,
        public string $totalFormatted,
        public array $items = [],
    ) {}
}
