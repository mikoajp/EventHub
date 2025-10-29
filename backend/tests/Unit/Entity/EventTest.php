<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\TicketType;
use App\Entity\Ticket;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\EventStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Event
 * @group unit
 * @group entity
 */
final class EventTest extends TestCase
{
    public function testEventStatusConstants(): void
    {
        $this->assertSame('draft', Event::STATUS_DRAFT);
        $this->assertSame('published', Event::STATUS_PUBLISHED);
        $this->assertSame('cancelled', Event::STATUS_CANCELLED);
        $this->assertSame('completed', Event::STATUS_COMPLETED);
    }

    public function testCreateEventInitializesCollections(): void
    {
        $event = new Event();
        
        $this->assertInstanceOf(Event::class, $event);
        $this->assertCount(0, $event->getTicketTypes());
        $this->assertCount(0, $event->getTickets());
        $this->assertCount(0, $event->getOrders());
        $this->assertCount(0, $event->getAttendees());
    }

    public function testEventHasDefaultDraftStatus(): void
    {
        $event = new Event();
        
        $this->assertSame(EventStatus::DRAFT, $event->getStatus());
        $this->assertTrue($event->isDraft());
        $this->assertFalse($event->isPublished());
    }

    public function testSetAndGetName(): void
    {
        $event = new Event();
        $event->setName('Rock Concert');
        
        $this->assertSame('Rock Concert', $event->getName());
    }

    public function testSetNameReturnsInstanceForFluentInterface(): void
    {
        $event = new Event();
        $result = $event->setName('Test Event');
        
        $this->assertSame($event, $result);
    }

    public function testSetAndGetDescription(): void
    {
        $event = new Event();
        $event->setDescription('Amazing event with great music');
        
        $this->assertSame('Amazing event with great music', $event->getDescription());
    }

    public function testSetAndGetVenue(): void
    {
        $event = new Event();
        $event->setVenue('Main Arena');
        
        $this->assertSame('Main Arena', $event->getVenue());
    }

    public function testSetStatusWithEnum(): void
    {
        $event = new Event();
        $event->setStatus(EventStatus::PUBLISHED);
        
        $this->assertSame(EventStatus::PUBLISHED, $event->getStatus());
    }

    public function testSetStatusWithString(): void
    {
        $event = new Event();
        $event->setStatus('published');
        
        $this->assertSame(EventStatus::PUBLISHED, $event->getStatus());
    }

    public function testIsPublished(): void
    {
        $event = new Event();
        
        $event->setStatus(EventStatus::DRAFT);
        $this->assertFalse($event->isPublished());
        
        $event->setStatus(EventStatus::PUBLISHED);
        $this->assertTrue($event->isPublished());
    }

    public function testIsDraft(): void
    {
        $event = new Event();
        
        $this->assertTrue($event->isDraft());
        
        $event->setStatus(EventStatus::PUBLISHED);
        $this->assertFalse($event->isDraft());
    }

    public function testIsCancelled(): void
    {
        $event = new Event();
        
        $this->assertFalse($event->isCancelled());
        
        $event->setStatus(EventStatus::CANCELLED);
        $this->assertTrue($event->isCancelled());
    }

    public function testIsCompleted(): void
    {
        $event = new Event();
        
        $this->assertFalse($event->isCompleted());
        
        $event->setStatus(EventStatus::COMPLETED);
        $this->assertTrue($event->isCompleted());
    }

    public function testSetAndGetMaxTickets(): void
    {
        $event = new Event();
        $event->setMaxTickets(1000);
        
        $this->assertSame(1000, $event->getMaxTickets());
    }

    public function testSetAndGetEventDate(): void
    {
        $event = new Event();
        $date = new \DateTime('2025-12-31 20:00:00');
        
        $event->setEventDate($date);
        
        $this->assertSame($date, $event->getEventDate());
    }

    public function testSetEventDateWithString(): void
    {
        $event = new Event();
        $event->setEventDate('2025-12-31 20:00:00');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getEventDate());
        $this->assertSame('2025-12-31', $event->getEventDate()->format('Y-m-d'));
    }

    public function testSetAndGetOrganizer(): void
    {
        $event = new Event();
        $organizer = new User();
        $organizer->setEmail('organizer@example.com');
        
        $event->setOrganizer($organizer);
        
        $this->assertSame($organizer, $event->getOrganizer());
    }

    public function testAddTicketType(): void
    {
        $event = new Event();
        $ticketType = new TicketType();
        $ticketType->setName('VIP');
        
        $event->addTicketType($ticketType);
        
        $this->assertCount(1, $event->getTicketTypes());
        $this->assertTrue($event->getTicketTypes()->contains($ticketType));
        $this->assertSame($event, $ticketType->getEvent());
    }

    public function testAddTicketTypeDoesNotDuplicateIfAlreadyAdded(): void
    {
        $event = new Event();
        $ticketType = new TicketType();
        $ticketType->setName('VIP');
        
        $event->addTicketType($ticketType);
        $event->addTicketType($ticketType);
        
        $this->assertCount(1, $event->getTicketTypes());
    }

    public function testRemoveTicketType(): void
    {
        $event = new Event();
        $ticketType = new TicketType();
        $ticketType->setName('VIP');
        $ticketType->setPrice(5000);
        $ticketType->setQuantity(50);
        
        $event->addTicketType($ticketType);
        $this->assertCount(1, $event->getTicketTypes());
        $this->assertSame($event, $ticketType->getEvent());
        
        // Note: removeTicketType removes from collection but doesn't set event to null
        // because TicketType::event is non-nullable (orphan removal would delete it)
        $event->removeTicketType($ticketType);
        $this->assertCount(0, $event->getTicketTypes());
    }

    public function testAddTicket(): void
    {
        $event = new Event();
        $ticket = new Ticket();
        
        $event->addTicket($ticket);
        
        $this->assertCount(1, $event->getTickets());
        $this->assertTrue($event->getTickets()->contains($ticket));
    }

    public function testAddAttendee(): void
    {
        $event = new Event();
        $attendee = new User();
        $attendee->setEmail('attendee@example.com');
        
        $event->addAttendee($attendee);
        
        $this->assertCount(1, $event->getAttendees());
        $this->assertTrue($event->getAttendees()->contains($attendee));
    }

    public function testAddAttendeeDoesNotDuplicateIfAlreadyAdded(): void
    {
        $event = new Event();
        $attendee = new User();
        $attendee->setEmail('attendee@example.com');
        
        $event->addAttendee($attendee);
        $event->addAttendee($attendee);
        
        $this->assertCount(1, $event->getAttendees());
    }

    public function testRemoveAttendee(): void
    {
        $event = new Event();
        $attendee = new User();
        $attendee->setEmail('attendee@example.com');
        
        $event->addAttendee($attendee);
        $this->assertCount(1, $event->getAttendees());
        
        $event->removeAttendee($attendee);
        $this->assertCount(0, $event->getAttendees());
    }

    public function testSetAndGetPublishedAt(): void
    {
        $event = new Event();
        $publishedAt = new \DateTime('2025-01-15 10:00:00');
        
        $event->setPublishedAt($publishedAt);
        
        $this->assertSame($publishedAt, $event->getPublishedAt());
    }

    public function testSetAndGetCancelledAt(): void
    {
        $event = new Event();
        $cancelledAt = new \DateTime('2025-02-01 15:30:00');
        
        $event->setCancelledAt($cancelledAt);
        
        $this->assertSame($cancelledAt, $event->getCancelledAt());
    }

    public function testSetAndGetPreviousStatus(): void
    {
        $event = new Event();
        
        $event->setPreviousStatus(EventStatus::DRAFT);
        
        $this->assertSame(EventStatus::DRAFT, $event->getPreviousStatus());
    }

    public function testSetPreviousStatusWithString(): void
    {
        $event = new Event();
        
        $event->setPreviousStatus('published');
        
        $this->assertSame(EventStatus::PUBLISHED, $event->getPreviousStatus());
    }

    public function testSetPreviousStatusWithNull(): void
    {
        $event = new Event();
        
        $event->setPreviousStatus(null);
        
        $this->assertNull($event->getPreviousStatus());
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $event = new Event();
        
        $event->onPrePersist();
        
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getUpdatedAt());
        
        // Timestamps should be approximately now
        $now = new \DateTime();
        $this->assertEqualsWithDelta(
            $now->getTimestamp(),
            $event->getCreatedAt()->getTimestamp(),
            2,
            'CreatedAt should be set to current time'
        );
    }

    public function testOnPreUpdateUpdatesTimestamp(): void
    {
        $event = new Event();
        $event->onPrePersist();
        
        $originalUpdatedAt = $event->getUpdatedAt();
        
        // Wait a moment to ensure timestamp difference
        usleep(1000000); // 1 second to ensure different timestamp
        
        $event->onPreUpdate();
        
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $event->getUpdatedAt()->getTimestamp(),
            'UpdatedAt timestamp should be greater after onPreUpdate()'
        );
    }

    public function testFluentInterfaceForAllSetters(): void
    {
        $event = new Event();
        $organizer = new User();
        $organizer->setEmail('organizer@example.com');
        
        $result = $event
            ->setName('Concert')
            ->setDescription('Great concert')
            ->setVenue('Arena')
            ->setMaxTickets(500)
            ->setEventDate(new \DateTime('+1 month'))
            ->setStatus(EventStatus::PUBLISHED)
            ->setOrganizer($organizer);
        
        $this->assertSame($event, $result);
        $this->assertSame('Concert', $event->getName());
        $this->assertSame('Great concert', $event->getDescription());
        $this->assertSame('Arena', $event->getVenue());
        $this->assertSame(500, $event->getMaxTickets());
        $this->assertTrue($event->isPublished());
    }
}
