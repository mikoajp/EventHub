<?php

namespace App\Tests\Unit\Message\Event;

use App\Message\Event\TicketPurchasedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TicketPurchasedEventTest extends TestCase
{
    public function testCreateEvent(): void
    {
        $ticketId = Uuid::v4()->toString();
        $eventId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        $amount = 5000;
        $occurredAt = new \DateTimeImmutable();

        $event = new TicketPurchasedEvent($ticketId, $eventId, $userId, $amount, $occurredAt);

        $this->assertInstanceOf(TicketPurchasedEvent::class, $event);
        $this->assertSame($ticketId, $event->ticketId);
        $this->assertSame($eventId, $event->eventId);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame($occurredAt, $event->occurredAt);
    }

    public function testEventPropertiesArePublic(): void
    {
        $event = new TicketPurchasedEvent(
            'ticket-1',
            'event-1',
            'user-1',
            5000,
            new \DateTimeImmutable()
        );

        $this->assertIsString($event->ticketId);
        $this->assertIsString($event->eventId);
        $this->assertIsString($event->userId);
        $this->assertIsInt($event->amount);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    public function testOccurredAtIsImmutable(): void
    {
        $occurredAt = new \DateTimeImmutable('2025-01-15 10:00:00');
        $event = new TicketPurchasedEvent('ticket-1', 'event-1', 'user-1', 3000, $occurredAt);

        $this->assertSame($occurredAt, $event->occurredAt);
        
        // Verify it's truly immutable
        $modified = $event->occurredAt->modify('+1 day');
        $this->assertNotSame($modified, $event->occurredAt);
    }
}
