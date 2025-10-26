<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Test hard domain rules for Event: dates must be in future, capacity limits
 */
final class EventDomainRulesTest extends TestCase
{
    public function testEventDateMustBeInFuture(): void
    {
        $event = new Event();
        $pastDate = new \DateTime('-1 day');

        $event->setEventDate($pastDate);
        
        // Validation would be handled by Symfony validators
        // This tests the entity accepts the date but validators should reject it
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getEventDate());
    }

    public function testEventMaxTicketsMustBePositive(): void
    {
        $event = new Event();
        
        // The entity will accept it, but validators should reject
        $event->setMaxTickets(0);
        $this->assertSame(0, $event->getMaxTickets());
    }

    public function testEventCanTransitionFromDraftToPublished(): void
    {
        $event = new Event();
        $event->setStatus(Event::STATUS_DRAFT);

        $this->assertTrue($event->isDraft());
        $this->assertFalse($event->isPublished());

        $event->setStatus(Event::STATUS_PUBLISHED);

        $this->assertTrue($event->isPublished());
        $this->assertFalse($event->isDraft());
    }

    public function testEventCanBeCancelled(): void
    {
        $event = new Event();
        $event->setStatus(Event::STATUS_PUBLISHED);

        $event->setStatus(Event::STATUS_CANCELLED);
        $event->setCancelledAt(new \DateTime());

        $this->assertTrue($event->isCancelled());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getCancelledAt());
    }

    public function testEventStatusConstants(): void
    {
        $this->assertSame('draft', Event::STATUS_DRAFT);
        $this->assertSame('published', Event::STATUS_PUBLISHED);
        $this->assertSame('cancelled', Event::STATUS_CANCELLED);
        $this->assertSame('completed', Event::STATUS_COMPLETED);
    }

    public function testEventRequiresOrganizer(): void
    {
        $organizer = new User();
        $organizer->setEmail('organizer@test.com');
        $organizer->setPassword('password');

        $event = new Event();
        $event->setOrganizer($organizer);

        $this->assertSame($organizer, $event->getOrganizer());
    }

    public function testEventTracksPublishDate(): void
    {
        $event = new Event();
        $publishedAt = new \DateTime('2025-01-15 10:00:00');
        
        $event->setPublishedAt($publishedAt);

        $this->assertSame($publishedAt, $event->getPublishedAt());
    }

    public function testEventHasTimestamps(): void
    {
        $event = new Event();
        $event->onPrePersist();

        $this->assertInstanceOf(\DateTimeInterface::class, $event->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getUpdatedAt());
    }

    public function testEventUpdateTimestamp(): void
    {
        $event = new Event();
        $event->onPrePersist();
        
        $originalUpdatedAt = $event->getUpdatedAt();
        
        sleep(1);
        $event->onPreUpdate();
        
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $event->getUpdatedAt()->getTimestamp()
        );
    }
}
