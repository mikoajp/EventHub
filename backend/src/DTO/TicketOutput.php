<?php

namespace App\DTO;

final class TicketOutput
{
    public string $id;
    public string $status;
    public int $price;
    public string $priceFormatted;
    public string $createdAt;
    public ?string $purchasedAt = null;
    public ?string $qrCode = null;
    public array $event;
    public array $ticketType;
}
