<?php

namespace App\DTO;

readonly class EventListItemDTO
{
    /**
     * @param string $id
     * @param string $name :?string,name:?string,price:int,quantity:int,priceFormatted:string}> $ticketTypes
     * @param string $description
     * @param string $eventDate
     * @param string $eventDateFormatted
     * @param string $venue
     * @param int $maxTickets
     * @param string $status
     * @param string $statusLabel
     * @param string|null $publishedAt
     * @param string|null $createdAt
     * @param string|null $createdAtFormatted
     * @param array $organizer
     * @param array $ticketTypes
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $eventDate,
        public string $eventDateFormatted,
        public string $venue,
        public int $maxTickets,
        public string $status,
        public string $statusLabel,
        public ?string $publishedAt = null,
        public ?string $createdAt = null,
        public ?string $createdAtFormatted = null,
        public array $organizer = ['id' => null, 'name' => null],
        public array $ticketTypes = [],
    ) {}
}
