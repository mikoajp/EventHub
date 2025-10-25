<?php

namespace App\DTO;

final readonly class EventResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $eventDate,
        public string $venue,
        public int $maxTickets,
        public string $status,
        public ?string $publishedAt,
        public string $createdAt,
        public int $ticketsSold,
        public int $availableTickets,
    ) {}
}
