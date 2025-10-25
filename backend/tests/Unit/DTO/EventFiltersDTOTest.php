<?php

namespace App\Tests\Unit\DTO;

use App\DTO\EventFiltersDTO;
use PHPUnit\Framework\TestCase;

final class EventFiltersDTOTest extends TestCase
{
    public function testToArrayAndSorting(): void
    {
        $dto = new EventFiltersDTO(
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

        $arr = $dto->toArray();
        $this->assertSame('rock', $arr['search']);
        $this->assertSame(['published'], $arr['status']);
        $this->assertSame(['Main Hall'], $arr['venue']);
        $this->assertSame('uuid-1', $arr['organizer_id']);
        $this->assertSame('2025-01-01', $arr['date_from']);
        $this->assertSame('2025-12-31', $arr['date_to']);
        $this->assertSame(10.0, $arr['price_min']);
        $this->assertSame(100.0, $arr['price_max']);
        $this->assertTrue($arr['has_available_tickets']);
        $this->assertSame(['by' => 'date', 'direction' => 'desc'], $dto->getSorting());
        $this->assertSame(2, $arr['page']);
        $this->assertSame(50, $arr['limit']);
    }
}
