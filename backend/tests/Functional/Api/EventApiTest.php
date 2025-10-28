<?php

namespace App\Tests\Functional\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for Event API endpoints
 */
final class EventApiTest extends BaseWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    
    public function testGetEventsReturnsJsonArray(): void
    {
        // Act
        $response = $this->jsonRequest('GET', '/api/events');

        // Assert
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);
        $this->assertIsArray($data);
    }

    public function testGetEventsReturnsPublishedEventsOnly(): void
    {
        // Arrange
        $user = $this->createUser(); // Use unique email
        $this->persistAndFlush($user);

        $publishedEvent = $this->createEvent($user, 'Published Event', Event::STATUS_PUBLISHED);
        $draftEvent = $this->createEvent($user, 'Draft Event', Event::STATUS_DRAFT);
        
        $this->persistAndFlush($publishedEvent);
        $this->persistAndFlush($draftEvent);

        // Act
        $response = $this->jsonRequest('GET', '/api/events');

        // Assert
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);
        
        // API returns {events: [...], pagination: {...}}
        $events = $data['events'] ?? $data;
        $eventNames = array_column($events, 'name');
        $this->assertContains('Published Event', $eventNames);
        $this->assertNotContains('Draft Event', $eventNames);
    }

    public function testGetEventByIdReturnsEventDetails(): void
    {
        // Arrange
        $user = $this->createUser(); // Use unique email
        $this->persistAndFlush($user);

        $event = $this->createEvent($user, 'Detailed Event', Event::STATUS_PUBLISHED);
        $this->persistAndFlush($event);
        
        $eventId = $event->getId()->toString();

        // Act
        $response = $this->jsonRequest('GET', "/api/events/{$eventId}");

        // Assert
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);
        $this->assertSame('Detailed Event', $data['name']);
        $this->assertSame('Test Venue', $data['venue']);
    }

    public function testGetEventByInvalidIdReturnsNotFound(): void
    {
        // Act
        $response = $this->jsonRequest('GET', '/api/events/00000000-0000-0000-0000-000000000000');

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateEventRequiresAuthentication(): void
    {
        // Act
        $response = $this->jsonRequest('POST', '/api/events', [
            'name' => 'New Event',
            'description' => 'Description',
            'venue' => 'Venue',
            'eventDate' => '2025-12-31',
            'maxTickets' => 100
        ]);

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN]),
            'Creating event should require authentication'
        );
    }

    public function testFilterEventsBySearchTerm(): void
    {
        // Arrange
        $user = $this->createUser(); // Use unique email
        $this->persistAndFlush($user);

        $rockEvent = $this->createEvent($user, 'Rock Concert', Event::STATUS_PUBLISHED);
        $jazzEvent = $this->createEvent($user, 'Jazz Night', Event::STATUS_PUBLISHED);
        
        $this->persistAndFlush($rockEvent);
        $this->persistAndFlush($jazzEvent);

        // Act
        $response = $this->jsonRequest('GET', '/api/events?search=rock');

        // Assert
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);
        
        // API returns {events: [...], pagination: {...}}
        $events = $data['events'] ?? $data;
        $eventNames = array_column($events, 'name');
        $this->assertContains('Rock Concert', $eventNames);
        $this->assertNotContains('Jazz Night', $eventNames);
    }

    private function createUser(string $email = null): User
    {
        $user = new User();
        $user->setEmail($email ?? 'test-' . uniqid() . '@test.com');
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
        
        // Set publishedAt if status is published
        if ($status === Event::STATUS_PUBLISHED) {
            $event->setPublishedAt(new \DateTime());
        }
        
        return $event;
    }
}
