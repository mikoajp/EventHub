<?php

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Event\Service\EventDomainService;
use App\DTO\EventDTO;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class EventDomainServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EventDomainService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new EventDomainService($this->entityManager);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(EventDomainService::class, $this->service);
    }

    public function testCanUserModifyEventReturnsTrueForOrganizer(): void
    {
        $user = $this->createMock(User::class);
        $event = $this->createMock(Event::class);
        
        $event->method('getOrganizer')->willReturn($user);
        
        $result = $this->service->canUserModifyEvent($event, $user);
        
        $this->assertTrue($result);
    }

    public function testCanUserModifyEventReturnsFalseForDifferentUser(): void
    {
        $organizer = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);
        
        $event = $this->createMock(Event::class);
        $event->method('getOrganizer')->willReturn($organizer);
        
        $result = $this->service->canUserModifyEvent($event, $otherUser);
        
        $this->assertFalse($result);
    }

    public function testIsEventPublishableReturnsTrueForDraftEvent(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getStatus')->willReturn(Event::STATUS_DRAFT);
        $event->method('getName')->willReturn('Test Event');
        $event->method('getEventDate')->willReturn(new \DateTime('+1 month'));
        $event->method('getVenue')->willReturn('Test Venue');
        
        $result = $this->service->isEventPublishable($event);
        
        $this->assertTrue($result);
    }

    public function testIsEventPublishableReturnsFalseForPublishedEvent(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getStatus')->willReturn(Event::STATUS_PUBLISHED);
        
        $result = $this->service->isEventPublishable($event);
        
        $this->assertFalse($result);
    }

    public function testIsEventPublishableReturnsFalseForCancelledEvent(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getStatus')->willReturn(Event::STATUS_CANCELLED);
        
        $result = $this->service->isEventPublishable($event);
        
        $this->assertFalse($result);
    }

    public function testIsEventPublishableReturnsFalseWhenNoName(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getStatus')->willReturn(Event::STATUS_DRAFT);
        $event->method('getName')->willReturn(null);
        $event->method('getEventDate')->willReturn(new \DateTime('+1 month'));
        $event->method('getVenue')->willReturn('Test Venue');
        
        $result = $this->service->isEventPublishable($event);
        
        $this->assertFalse($result);
    }
}
