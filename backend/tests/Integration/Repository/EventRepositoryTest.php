<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Enum\EventStatus;
use App\Repository\EventRepository;
use App\Tests\BaseTestCase;

/**
 * @covers \App\Repository\EventRepository
 * @group integration
 * @group repository
 * @group database
 */
final class EventRepositoryTest extends BaseTestCase
{
    private EventRepository $eventRepository;
    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->eventRepository = $this->entityManager->getRepository(Event::class);
        
        // Create an organizer for events
        $this->organizer = new User();
        $this->organizer->setEmail('organizer@test.com');
        $this->organizer->setPassword('hashed_password');
        $this->organizer->setFirstName('Test');
        $this->organizer->setLastName('Organizer');
        
        $this->entityManager->persist($this->organizer);
        $this->entityManager->flush();
    }

    public function testFindPublishedEvents(): void
    {
        // Create draft event
        $draftEvent = $this->createEvent('Draft Event', EventStatus::DRAFT);
        
        // Create published events
        $publishedEvent1 = $this->createEvent('Published Event 1', EventStatus::PUBLISHED);
        $publishedEvent1->setPublishedAt(new \DateTime('-1 day'));
        
        $publishedEvent2 = $this->createEvent('Published Event 2', EventStatus::PUBLISHED);
        $publishedEvent2->setPublishedAt(new \DateTime('-2 days'));
        
        // Create cancelled event
        $cancelledEvent = $this->createEvent('Cancelled Event', EventStatus::CANCELLED);
        
        $this->entityManager->flush();
        
        // Find only published events
        $publishedEvents = $this->eventRepository->findBy(['status' => EventStatus::PUBLISHED]);
        
        $this->assertCount(2, $publishedEvents);
        
        $names = array_map(fn($e) => $e->getName(), $publishedEvents);
        $this->assertContains('Published Event 1', $names);
        $this->assertContains('Published Event 2', $names);
        $this->assertNotContains('Draft Event', $names);
        $this->assertNotContains('Cancelled Event', $names);
    }

    public function testFindByUuid(): void
    {
        $event = $this->createEvent('Test Event', EventStatus::PUBLISHED);
        $this->entityManager->flush();
        
        $uuid = $event->getId();
        $this->assertNotNull($uuid);
        
        // Find by UUID
        $foundEvent = $this->eventRepository->find($uuid);
        
        $this->assertNotNull($foundEvent);
        $this->assertSame($event->getName(), $foundEvent->getName());
        $this->assertEquals($uuid, $foundEvent->getId());
    }

    public function testFindByNonexistentUuidReturnsNull(): void
    {
        $nonexistentUuid = \Symfony\Component\Uid\Uuid::v4();
        
        $foundEvent = $this->eventRepository->find($nonexistentUuid);
        
        $this->assertNull($foundEvent);
    }

    public function testFindByOrganizer(): void
    {
        // Create another organizer
        $otherOrganizer = new User();
        $otherOrganizer->setEmail('other@test.com');
        $otherOrganizer->setPassword('hashed_password');
        $otherOrganizer->setFirstName('Other');
        $otherOrganizer->setLastName('Organizer');
        $this->entityManager->persist($otherOrganizer);
        
        // Create events for different organizers
        $event1 = $this->createEvent('Event 1', EventStatus::PUBLISHED);
        
        $event2 = new Event();
        $event2->setName('Event 2');
        $event2->setDescription('Description 2');
        $event2->setVenue('Venue 2');
        $event2->setEventDate(new \DateTime('+2 months'));
        $event2->setMaxTickets(200);
        $event2->setOrganizer($otherOrganizer);
        $this->entityManager->persist($event2);
        
        $this->entityManager->flush();
        
        // Find events by organizer
        $organizerEvents = $this->eventRepository->findBy(['organizer' => $this->organizer]);
        
        $this->assertCount(1, $organizerEvents);
        $this->assertSame('Event 1', $organizerEvents[0]->getName());
    }

    public function testFindEventsWithDateRange(): void
    {
        // Create events at different dates
        $pastEvent = $this->createEvent('Past Event', EventStatus::PUBLISHED);
        $pastEvent->setEventDate(new \DateTime('-1 month'));
        
        $upcomingEvent = $this->createEvent('Upcoming Event', EventStatus::PUBLISHED);
        $upcomingEvent->setEventDate(new \DateTime('+1 month'));
        
        $farFutureEvent = $this->createEvent('Far Future Event', EventStatus::PUBLISHED);
        $farFutureEvent->setEventDate(new \DateTime('+6 months'));
        
        $this->entityManager->flush();
        
        // Find events in date range (next 3 months)
        $qb = $this->eventRepository->createQueryBuilder('e');
        $qb->where('e.eventDate >= :start')
           ->andWhere('e.eventDate <= :end')
           ->setParameter('start', new \DateTime('now'))
           ->setParameter('end', new \DateTime('+3 months'));
        
        $eventsInRange = $qb->getQuery()->getResult();
        
        $this->assertCount(1, $eventsInRange);
        $this->assertSame('Upcoming Event', $eventsInRange[0]->getName());
    }

    public function testFindWithPagination(): void
    {
        // Create multiple events
        for ($i = 1; $i <= 10; $i++) {
            $this->createEvent("Event {$i}", EventStatus::PUBLISHED);
        }
        $this->entityManager->flush();
        
        // Get first page (5 results)
        $qb = $this->eventRepository->createQueryBuilder('e');
        $qb->setMaxResults(5)
           ->setFirstResult(0)
           ->orderBy('e.createdAt', 'DESC');
        
        $firstPage = $qb->getQuery()->getResult();
        
        $this->assertCount(5, $firstPage);
        
        // Get second page
        $qb2 = $this->eventRepository->createQueryBuilder('e');
        $qb2->setMaxResults(5)
            ->setFirstResult(5)
            ->orderBy('e.createdAt', 'DESC');
        
        $secondPage = $qb2->getQuery()->getResult();
        
        $this->assertCount(5, $secondPage);
        
        // Ensure different results
        $firstIds = array_map(fn($e) => $e->getId()->toString(), $firstPage);
        $secondIds = array_map(fn($e) => $e->getId()->toString(), $secondPage);
        
        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }

    public function testFindWithSorting(): void
    {
        // Create events with different names
        $eventB = $this->createEvent('B Event', EventStatus::PUBLISHED);
        $eventA = $this->createEvent('A Event', EventStatus::PUBLISHED);
        $eventC = $this->createEvent('C Event', EventStatus::PUBLISHED);
        
        $this->entityManager->flush();
        
        // Sort by name ascending
        $qb = $this->eventRepository->createQueryBuilder('e');
        $qb->orderBy('e.name', 'ASC');
        
        $sorted = $qb->getQuery()->getResult();
        
        $this->assertSame('A Event', $sorted[0]->getName());
        $this->assertSame('B Event', $sorted[1]->getName());
        $this->assertSame('C Event', $sorted[2]->getName());
    }

    public function testCountPublishedEvents(): void
    {
        // Create mix of events
        $this->createEvent('Draft 1', EventStatus::DRAFT);
        $this->createEvent('Published 1', EventStatus::PUBLISHED);
        $this->createEvent('Published 2', EventStatus::PUBLISHED);
        $this->createEvent('Published 3', EventStatus::PUBLISHED);
        $this->createEvent('Cancelled 1', EventStatus::CANCELLED);
        
        $this->entityManager->flush();
        
        // Count published events
        $count = $this->eventRepository->count(['status' => EventStatus::PUBLISHED]);
        
        $this->assertSame(3, $count);
    }

    public function testFindEventsByVenue(): void
    {
        $event1 = $this->createEvent('Event 1', EventStatus::PUBLISHED);
        $event1->setVenue('Arena A');
        
        $event2 = $this->createEvent('Event 2', EventStatus::PUBLISHED);
        $event2->setVenue('Arena A');
        
        $event3 = $this->createEvent('Event 3', EventStatus::PUBLISHED);
        $event3->setVenue('Stadium B');
        
        $this->entityManager->flush();
        
        $arenaEvents = $this->eventRepository->findBy(['venue' => 'Arena A']);
        
        $this->assertCount(2, $arenaEvents);
    }

    public function testSearchEventsByName(): void
    {
        $this->createEvent('Rock Concert', EventStatus::PUBLISHED);
        $this->createEvent('Jazz Festival', EventStatus::PUBLISHED);
        $this->createEvent('Rock and Roll Night', EventStatus::PUBLISHED);
        $this->createEvent('Classical Music', EventStatus::PUBLISHED);
        
        $this->entityManager->flush();
        
        // Search for events containing "Rock"
        $qb = $this->eventRepository->createQueryBuilder('e');
        $qb->where('e.name LIKE :search')
           ->setParameter('search', '%Rock%');
        
        $rockEvents = $qb->getQuery()->getResult();
        
        $this->assertCount(2, $rockEvents);
    }

    public function testFindUpcomingEvents(): void
    {
        // Create past and future events
        $pastEvent = $this->createEvent('Past Event', EventStatus::PUBLISHED);
        $pastEvent->setEventDate(new \DateTime('-1 week'));
        
        $upcomingEvent1 = $this->createEvent('Upcoming 1', EventStatus::PUBLISHED);
        $upcomingEvent1->setEventDate(new \DateTime('+1 week'));
        
        $upcomingEvent2 = $this->createEvent('Upcoming 2', EventStatus::PUBLISHED);
        $upcomingEvent2->setEventDate(new \DateTime('+2 weeks'));
        
        $this->entityManager->flush();
        
        // Find only future events
        $qb = $this->eventRepository->createQueryBuilder('e');
        $qb->where('e.eventDate > :now')
           ->andWhere('e.status = :status')
           ->setParameter('now', new \DateTime())
           ->setParameter('status', EventStatus::PUBLISHED)
           ->orderBy('e.eventDate', 'ASC');
        
        $upcomingEvents = $qb->getQuery()->getResult();
        
        $this->assertCount(2, $upcomingEvents);
        $this->assertSame('Upcoming 1', $upcomingEvents[0]->getName());
        $this->assertSame('Upcoming 2', $upcomingEvents[1]->getName());
    }

    public function testPersistAndFlush(): void
    {
        $event = $this->createEvent('New Event', EventStatus::DRAFT);
        
        // Before flush, should not be in database
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        // After flush and clear, should be retrievable
        $foundEvent = $this->eventRepository->findOneBy(['name' => 'New Event']);
        
        $this->assertNotNull($foundEvent);
        $this->assertSame('New Event', $foundEvent->getName());
    }

    public function testRemoveEvent(): void
    {
        $event = $this->createEvent('To Delete', EventStatus::DRAFT);
        $this->entityManager->flush();
        
        $id = $event->getId();
        
        // Remove event
        $this->entityManager->remove($event);
        $this->entityManager->flush();
        
        // Should not be found
        $foundEvent = $this->eventRepository->find($id);
        
        $this->assertNull($foundEvent);
    }

    public function testEventLifecycleCallbacks(): void
    {
        $event = $this->createEvent('Lifecycle Test', EventStatus::DRAFT);
        
        // Before persist, timestamps should be null
        $this->assertNull($event->getCreatedAt());
        $this->assertNull($event->getUpdatedAt());
        
        $this->entityManager->flush();
        
        // After persist, timestamps should be set
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getUpdatedAt());
        
        $originalUpdatedAt = $event->getUpdatedAt();
        
        // Update event
        usleep(100000); // 0.1 second
        $event->setName('Updated Name');
        $this->entityManager->flush();
        
        // UpdatedAt should change
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $event->getUpdatedAt()->getTimestamp()
        );
    }

    /**
     * Helper method to create an event
     */
    private function createEvent(string $name, EventStatus $status): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setDescription('Test description for ' . $name);
        $event->setVenue('Test Venue');
        $event->setEventDate(new \DateTime('+1 month'));
        $event->setMaxTickets(100);
        $event->setStatus($status);
        $event->setOrganizer($this->organizer);
        
        $this->entityManager->persist($event);
        
        return $event;
    }
}
