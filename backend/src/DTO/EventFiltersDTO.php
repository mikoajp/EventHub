<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class EventFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        
        #[Assert\Choice(choices: ['published', 'draft', 'cancelled', 'completed'], multiple: true)]
        public array $status = ['published'],
        
        public array $venue = [],
        
        public ?string $organizer_id = null,
        
        #[Assert\Date]
        public ?string $date_from = null,
        
        #[Assert\Date]
        public ?string $date_to = null,
        
        #[Assert\PositiveOrZero]
        public ?float $price_min = null,
        
        #[Assert\PositiveOrZero]
        public ?float $price_max = null,
        
        public bool $has_available_tickets = false,
        
        #[Assert\Choice(choices: ['date', 'name', 'price', 'popularity', 'created_at', 'venue'])]
        public string $sort_by = 'date',
        
        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $sort_direction = 'asc',
        
        #[Assert\Positive]
        public int $page = 1,
        
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20
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
        ];
    }

    public function getSorting(): array
    {
        return [
            'field' => $this->sort_by,
            'direction' => $this->sort_direction,
        ];
    }
}