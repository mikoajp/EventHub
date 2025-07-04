<?php

namespace App\Application\Service;

use App\Domain\Event\Service\EventDomainService;
use App\Domain\Event\Service\EventPublishingService;
use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use App\Infrastructure\Messaging\MessageBusInterface;
use App\Infrastructure\Email\EmailServiceInterface;
use App\Repository\UserRepository;

final readonly class EventApplicationService
{
    private const CACHE_TTL_PUBLISHED_EVENTS = 1800; // 30 minutes
    private const CACHE_TTL_SINGLE_EVENT = 3600; // 1 hour
    private const CACHE_KEY_PUBLISHED_EVENTS = 'events.published';
    private const CACHE_KEY_EVENT_PREFIX = 'event.';

    public function __construct(
        private EventDomainService $eventDomainService,
        private EventPublishingService $eventPublishingService,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private CacheInterface $cache,
        private MessageBusInterface $messageBus,
        private EmailServiceInterface $emailService
    ) {}

    public function createEvent(EventDTO $eventDTO, User $organizer): Event
    {
        $event = $this->eventDomainService->createEvent($eventDTO, $organizer);
        
        // Invalidate cache
        $this->cache->deletePattern('events.*');
        
        return $event;
    }

    public function updateEvent(Event $event, EventDTO $eventDTO, User $user): Event
    {
        if (!$this->eventDomainService->canUserModifyEvent($event, $user)) {
            throw new \InvalidArgumentException('User has no permission to modify this event');
        }

        $updatedEvent = $this->eventDomainService->updateEvent($event, $eventDTO);
        
        // Invalidate cache
        $this->cache->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId()->toString());
        $this->cache->deletePattern('events.*');
        
        return $updatedEvent;
    }

    public function publishEvent(Event $event, User $publisher): void
    {
        if (!$this->eventDomainService->isEventPublishable($event)) {
            throw new \InvalidArgumentException('Event is not publishable');
        }

        $publishedAt = $this->eventPublishingService->publishEvent($event, $publisher);

        // Send notifications
        $this->sendEventPublishedNotifications($event);

        // Invalidate cache
        $this->cache->deletePattern('events.*');
        $this->cache->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId()->toString());
    }

    public function getPublishedEvents(): array
    {
        return $this->cache->get(
            self::CACHE_KEY_PUBLISHED_EVENTS,
            fn() => $this->eventRepository->findPublishedEvents(),
            self::CACHE_TTL_PUBLISHED_EVENTS
        );
    }

    public function getEventById(string $eventId): ?Event
    {
        return $this->cache->get(
            self::CACHE_KEY_EVENT_PREFIX . $eventId,
            fn() => $this->eventRepository->find($eventId),
            self::CACHE_TTL_SINGLE_EVENT
        );
    }

    public function getEventsWithFilters(array $filters = [], array $sorting = [], int $page = 1, int $limit = 20): array
    {
        // Create cache key based on filters
        $cacheKey = 'events.filtered.' . md5(serialize([$filters, $sorting, $page, $limit]));
        
        return $this->cache->get(
            $cacheKey,
            fn() => $this->eventRepository->findEventsWithFilters($filters, $sorting, $page, $limit),
            300 // 5 minutes cache for filtered results
        );
    }

    public function getFilterOptions(): array
    {
        return $this->cache->get(
            'events.filter_options',
            fn() => [
                'venues' => $this->eventRepository->getUniqueVenues(),
                'priceRange' => $this->eventRepository->getPriceRange(),
                'statuses' => [
                    ['value' => 'published', 'label' => 'Published'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                    ['value' => 'completed', 'label' => 'Completed']
                ]
            ],
            1800 // 30 minutes cache
        );
    }

    private function sendEventPublishedNotifications(Event $event): void
    {
        // Send emails to all subscribers
        $subscribers = $this->userRepository->findAll();
        
        foreach ($subscribers as $subscriber) {
            $this->emailService->sendEventPublishedNotification($event, $subscriber);
        }

        // Publish to message bus for real-time notifications
        $eventData = [
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getName(),
            'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
            'venue' => $event->getVenue(),
            'message' => "New event published: {$event->getName()}",
            'timestamp' => (new \DateTime())->format('c')
        ];

        $this->messageBus->publishEvent($eventData);

        foreach ($subscribers as $subscriber) {
            $this->messageBus->publishNotification([
                'title' => 'New Event Available',
                'message' => "Check out the new event: {$event->getName()}",
                'type' => 'info',
                'event_id' => $event->getId()->toString()
            ], $subscriber->getId()->toString());
        }
    }
}