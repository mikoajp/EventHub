<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Service\CacheService;

class EventService
{
    private const CACHE_TTL_PUBLISHED_EVENTS = 1800; // 30 minutes
    private const CACHE_TTL_SINGLE_EVENT = 3600; // 1 hour
    private const CACHE_KEY_PUBLISHED_EVENTS = 'events.published';
    private const CACHE_KEY_EVENT_PREFIX = 'event.';

    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager,
        private CacheService $cacheService
    ) {}

    public function getPublishedEvents(): array
    {
        return $this->cacheService->get(self::CACHE_KEY_PUBLISHED_EVENTS, function() {
            return $this->eventRepository->findBy(['status' => 'published']);
        }, self::CACHE_TTL_PUBLISHED_EVENTS);
    }

    public function findEventOrFail(string $id): Event
    {
        $cacheKey = self::CACHE_KEY_EVENT_PREFIX . $id;
        
        return $this->cacheService->get($cacheKey, function() use ($id) {
            $event = $this->eventRepository->find($id);
            if (!$event) {
                throw new \RuntimeException('Event not found', Response::HTTP_NOT_FOUND);
            }
            return $event;
        }, self::CACHE_TTL_SINGLE_EVENT);
    }

    public function createEventFromDTO(EventDTO $eventDTO, User $user): Event
    {
        $event = new Event();
        $event->setName($eventDTO->name)
            ->setDescription($eventDTO->description)
            ->setEventDate($eventDTO->eventDate)
            ->setVenue($eventDTO->venue)
            ->setMaxTickets($eventDTO->maxTickets)
            ->setOrganizer($user)
            ->setStatus(Event::STATUS_DRAFT);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function publishEvent(string $id, User $user): void
    {
        $event = $this->findEventOrFail($id);

        if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
        }

        $event->setStatus('published');
        $this->entityManager->flush();
        
        $this->invalidateEventCache($event);
    }

    public function formatEventsCollectionResponse(array $events): array
    {
        return [
            '@context' => '/api/contexts/Event',
            '@id' => '/api/events',
            '@type' => 'hydra:Collection',
            'hydra:member' => array_map([$this, 'formatEventData'], $events),
            'hydra:totalItems' => count($events)
        ];
    }

    public function formatSingleEventResponse(Event $event): array
    {
        return $this->formatEventData($event);
    }

    public function formatEventCreationResponse(Event $event): array
    {
        return [
            'id' => $event->getId()->toString(),
            'message' => 'Event created successfully',
            'event' => $this->formatEventData($event)
        ];
    }

    private function formatEventData(Event $event): array
    {
        return [
            'id' => $event->getId()->toString(),
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'eventDate' => $event->getEventDate()->format('c'),
            'venue' => $event->getVenue(),
            'maxTickets' => $event->getMaxTickets(),
            'ticketsSold' => $event->getTicketsSold(),
            'availableTickets' => $event->getAvailableTickets(),
            'status' => $event->getStatus(),
            'organizer' => $this->formatOrganizerData($event->getOrganizer()),
            'ticketTypes' => array_map([$this, 'formatTicketTypeData'], $event->getTicketTypes()->toArray()),
        ];
    }

    private function formatOrganizerData(User $organizer): array
    {
        return [
            'id' => $organizer->getId()->toString(),
            'fullName' => $organizer->getFullName(),
        ];
    }

    private function formatTicketTypeData($ticketType): array
    {
        return [
            'id' => $ticketType->getId()->toString(),
            'name' => $ticketType->getName(),
            'price' => $ticketType->getPrice(),
            'priceFormatted' => number_format($ticketType->getPrice() / 100, 2),
            'quantity' => $ticketType->getQuantity(),
            'available' => $ticketType->getAvailableQuantity(),
        ];
    }

    public function validateUser(?User $user): void
    {
        if (!$user) {
            throw new \RuntimeException('User not authenticated', Response::HTTP_UNAUTHORIZED);
        }
    }
    
    private function invalidateEventCache(Event $event): void
    {
        $this->cacheService->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId());
        
        $this->cacheService->delete(self::CACHE_KEY_PUBLISHED_EVENTS);
    }
}