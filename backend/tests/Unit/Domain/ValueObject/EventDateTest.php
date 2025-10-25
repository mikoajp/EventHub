<?php

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\EventDate;
use PHPUnit\Framework\TestCase;

final class EventDateTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $eventDate = EventDate::fromString('2025-12-31 23:59:59');
        
        $this->assertInstanceOf(EventDate::class, $eventDate);
        $this->assertInstanceOf(\DateTimeImmutable::class, $eventDate->toNative());
    }

    public function testCreateFromStringWithTimezone(): void
    {
        $tz = new \DateTimeZone('America/New_York');
        $eventDate = EventDate::fromString('2025-12-31 23:59:59', $tz);
        
        $this->assertSame('America/New_York', $eventDate->toNative()->getTimezone()->getName());
    }

    public function testCreateFromNative(): void
    {
        $dateTime = new \DateTime('2025-06-15 10:00:00');
        $eventDate = EventDate::fromNative($dateTime);
        
        $this->assertInstanceOf(EventDate::class, $eventDate);
        $this->assertSame('2025-06-15', $eventDate->toNative()->format('Y-m-d'));
    }

    public function testFormat(): void
    {
        $eventDate = EventDate::fromString('2025-06-15 14:30:00');
        
        $this->assertSame('2025-06-15', $eventDate->format('Y-m-d'));
        $this->assertSame('15/06/2025', $eventDate->format('d/m/Y'));
    }

    public function testFormatDefault(): void
    {
        $eventDate = EventDate::fromString('2025-06-15 14:30:00');
        $formatted = $eventDate->format();
        
        // Default format is DATE_ATOM
        $this->assertStringContainsString('2025-06-15', $formatted);
        $this->assertStringContainsString('14:30:00', $formatted);
    }

    public function testIsFutureReturnsTrueForFutureDate(): void
    {
        $futureDate = new \DateTimeImmutable('+1 year');
        $eventDate = EventDate::fromNative($futureDate);
        
        $this->assertTrue($eventDate->isFuture());
    }

    public function testIsFutureReturnsFalseForPastDate(): void
    {
        $pastDate = new \DateTimeImmutable('-1 year');
        $eventDate = EventDate::fromNative($pastDate);
        
        $this->assertFalse($eventDate->isFuture());
    }

    public function testIsFutureReturnsFalseForCurrentTime(): void
    {
        $now = new \DateTimeImmutable();
        $eventDate = EventDate::fromNative($now);
        
        // Current time should not be considered future
        $this->assertFalse($eventDate->isFuture());
    }

    public function testEquality(): void
    {
        $date1 = EventDate::fromString('2025-06-15 10:00:00');
        $date2 = EventDate::fromString('2025-06-15 10:00:00');
        $date3 = EventDate::fromString('2025-06-15 11:00:00');
        
        $this->assertTrue($date1->equals($date2));
        $this->assertFalse($date1->equals($date3));
    }

    public function testImmutability(): void
    {
        $eventDate = EventDate::fromString('2025-06-15 10:00:00');
        $native = $eventDate->toNative();
        
        // Modify the native date
        $modified = $native->modify('+1 day');
        
        // Original should remain unchanged
        $this->assertNotSame($native->format('Y-m-d'), $modified->format('Y-m-d'));
        $this->assertSame('2025-06-15', $eventDate->format('Y-m-d'));
    }

    public function testInvalidDateStringThrowsException(): void
    {
        $this->expectException(\Exception::class);
        
        EventDate::fromString('invalid-date');
    }
}
