<?php

namespace App\DTO;

final readonly class EventFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        /** @var string[] */
        public array $status = ['published'],
        /** @var string[] */
        public array $venue = [],
        public ?string $organizer_id = null,
        public ?string $date_from = null,
        public ?string $date_to = null,
        public ?float $price_min = null,
        public ?float $price_max = null,
        public bool $has_available_tickets = false,
        public string $sort_by = 'date',
        public string $sort_direction = 'asc',
        public int $page = 1,
        public int $limit = 20,
    ) {}

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'venue' => $this->venue,
            'organizer_id' => $this->organizer_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'has_available_tickets' => $this->has_available_tickets,
            'sort_by' => $this->sort_by,
            'sort_direction' => $this->sort_direction,
            'page' => $this->page,
            'limit' => $this->limit,
        ];
    }

    public function getSorting(): array
    {
        return [
            'by' => $this->sort_by,
            'direction' => $this->sort_direction,
        ];
    }
}
