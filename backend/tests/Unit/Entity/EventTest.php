<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testEventStatusConstants(): void
    {
        $this->assertSame('draft', Event::STATUS_DRAFT);
        $this->assertSame('published', Event::STATUS_PUBLISHED);
        $this->assertSame('cancelled', Event::STATUS_CANCELLED);
        $this->assertSame('completed', Event::STATUS_COMPLETED);
    }

    public function testCreateEvent(): void
    {
        $event = new Event();
        
        $this->assertInstanceOf(Event::class, $event);
        // ID is null until persisted in Doctrine
    }

    public function testSetAndGetName(): void
    {
        $event = new Event();
        $event->setName('Rock Concert');
        
        $this->assertSame('Rock Concert', $event->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $event = new Event();
        $event->setDescription('Amazing event');
        
        $this->assertSame('Amazing event', $event->getDescription());
    }

    public function testSetAndGetVenue(): void
    {
        $event = new Event();
        $event->setVenue('Main Arena');
        
        $this->assertSame('Main Arena', $event->getVenue());
    }

    public function testSetAndGetStatus(): void
    {
        $event = new Event();
        $event->setStatus(Event::STATUS_PUBLISHED);
        
        $this->assertSame(Event::STATUS_PUBLISHED, $event->getStatus());
    }

    public function testIsPublished(): void
    {
        $event = new Event();
        $event->setStatus(Event::STATUS_DRAFT);
        
        $this->assertFalse($event->isPublished());
        
        $event->setStatus(Event::STATUS_PUBLISHED);
        $this->assertTrue($event->isPublished());
    }

    public function testSetAndGetMaxTickets(): void
    {
        $event = new Event();
        $event->setMaxTickets(1000);
        
        $this->assertSame(1000, $event->getMaxTickets());
    }

    public function testTicketTypesCollectionInitialized(): void
    {
        $event = new Event();
        
        $this->assertNotNull($event->getTicketTypes());
        $this->assertCount(0, $event->getTicketTypes());
    }
}
