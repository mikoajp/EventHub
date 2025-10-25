<?php

namespace App\Tests\Unit\DTO;

use App\DTO\EventFiltersDTO;
use PHPUnit\Framework\TestCase;

final class EventFiltersDTOTest extends TestCase
{
    public function testToArrayAndSorting(): void
    {
         = new EventFiltersDTO(
            search: 'rock',
            status: ['published'],
            venue: ['Main Hall'],
            organizer_id: 'uuid-1',
            date_from: '2025-01-01',
            date_to: '2025-12-31',
            price_min: 10.0,
            price_max: 100.0,
            has_available_tickets: true,
            sort_by: 'date',
            sort_direction: 'desc',
            page: 2,
            limit: 50,
        );

         = ->toArray();
        ->assertSame('rock', ['search']);
        ->assertSame(['published'], ['status']);
        ->assertSame(['Main Hall'], ['venue']);
        ->assertSame('uuid-1', ['organizer_id']);
        ->assertSame('2025-01-01', ['date_from']);
        ->assertSame('2025-12-31', ['date_to']);
        ->assertSame(10.0, ['price_min']);
        ->assertSame(100.0, ['price_max']);
        ->assertTrue(['has_available_tickets']);
        ->assertSame(['by' => 'date', 'direction' => 'desc'], ->getSorting());
        ->assertSame(2, ['page']);
        ->assertSame(50, ['limit']);
    }
}
