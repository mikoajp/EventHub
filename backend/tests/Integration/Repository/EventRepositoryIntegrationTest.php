<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Tests\BaseTestCase;

/**
 * Integration test for EventRepository
 * Tests actual database queries
 */
final class EventRepositoryIntegrationTest extends BaseTestCase
{
    private EventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(EventRepository::class);
    }

    public function testFindPublishedEventsReturnsOnlyPublished(): void
    {
        // Arrange
        $user = $this->createUser('organizer@test.com');
        $this->persistAndFlush($user);

        $publishedEvent = $this->createEvent($user, 'Published Event ' . uniqid(), Event::STATUS_PUBLISHED);
        $draftEvent = $this->createEvent($user, 'Draft Event ' . uniqid(), Event::STATUS_DRAFT);
        
        $this->persistAndFlush($publishedEvent);
        $this->persistAndFlush($draftEvent);
        
        $publishedEventId = $publishedEvent->getId()->toString();
        $this->clearEntityManager();

        // Act
        $result = $this->repository->findPublishedEvents();

        // Assert
        $this->assertGreaterThanOrEqual(1, count($result), 'Should return at least the published event we created');
        
        // Find our specific event in the results
        $foundOurEvent = false;
        $foundDraftEvent = false;
        foreach ($result as $event) {
            if ($event->getId()->toString() === $publishedEventId) {
                $foundOurEvent = true;
            }
            if ($event->getStatus() !== Event::STATUS_PUBLISHED) {
                $foundDraftEvent = true;
            }
        }
        
        $this->assertTrue($foundOurEvent, 'Our published event should be in results');
        $this->assertFalse($foundDraftEvent, 'No draft events should be in results');
    }

    public function testFindByUuidReturnsCorrectEvent(): void
    {
        // Arrange
        $user = $this->createUser('test-uuid-' . uniqid() . '@test.com');
        $this->persistAndFlush($user);

        $event = $this->createEvent($user, 'Test Event');
        $this->persistAndFlush($event);
        
        $uuid = $event->getId()->toString();
        $this->clearEntityManager();

        // Act
        $result = $this->repository->findByUuid($uuid);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('Test Event', $result->getName());
        $this->assertSame($uuid, $result->getId()->toString());
    }

    public function testFindByUuidReturnsNullForInvalidUuid(): void
    {
        // Act
        $result = $this->repository->findByUuid('00000000-0000-0000-0000-000000000000');

        // Assert
        $this->assertNull($result);
    }

    public function testGetUniqueVenuesReturnsDistinctVenues(): void
    {
        // Arrange
        $user = $this->createUser('test-venues-' . uniqid() . '@test.com');
        $this->persistAndFlush($user);

        // Use unique venue names to avoid conflicts with other tests
        $uniqueVenueA = 'Test Arena A ' . uniqid();
        $uniqueVenueB = 'Test Arena B ' . uniqid();

        $event1 = $this->createEvent($user, 'Event 1');
        $event1->setVenue($uniqueVenueA);
        
        $event2 = $this->createEvent($user, 'Event 2');
        $event2->setVenue($uniqueVenueB);
        
        $event3 = $this->createEvent($user, 'Event 3');
        $event3->setVenue($uniqueVenueA); // Duplicate
        
        $this->persistAndFlush($event1);
        $this->persistAndFlush($event2);
        $this->persistAndFlush($event3);

        // Act
        $result = $this->repository->getUniqueVenues();

        // Assert
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result), 'Should have at least our 2 unique venues');
        $this->assertContains($uniqueVenueA, $result);
        $this->assertContains($uniqueVenueB, $result);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        
        return $user;
    }

    private function createEvent(User $organizer, string $name, string $status = Event::STATUS_DRAFT): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setDescription('Test Description');
        $event->setVenue('Test Venue');
        $event->setEventDate(new \DateTime('+1 month'));
        $event->setMaxTickets(100);
        $event->setOrganizer($organizer);
        $event->setStatus($status);
        
        return $event;
    }
}
