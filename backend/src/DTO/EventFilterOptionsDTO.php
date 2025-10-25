<?php

namespace App\DTO;

/**
 * Filter options for building event search UI widgets.
 */
final readonly class EventFilterOptionsDTO
{
    /**
     * @param string[] $venues
     * @param array{min: float, max: float} $priceRange
     * @param array<int, array{value: string, label: string}> $statuses
     */
    public function __construct(
        public array $venues = [],
        public array $priceRange = ['min' => 0.0, 'max' => 0.0],
        public array $statuses = [],
    ) {}
}
