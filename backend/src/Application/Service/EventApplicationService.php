<?php

namespace App\Application\Service;

use App\Domain\Event\Service\EventDomainService;
use App\Domain\Event\Service\EventPublishingService;
use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
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
        private CacheInterface $cache,
        private NotificationApplicationService $notificationApplicationService
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

    public function cancelEvent(Event $event): void
    {
        // Domain service now handles validation internally
        $this->eventDomainService->cancelEvent($event);
        
        // Invalidate cache
        $this->cache->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId()->toString());
        $this->cache->deletePattern('events.*');
    }

    public function unpublishEvent(Event $event): void
    {
        // Get ticket count for validation
        $ticketsSold = $this->eventRepository->getTicketSalesStatistics($event)['total'] ?? 0;
        
        // Domain service now handles validation internally
        $this->eventDomainService->unpublishEvent($event, $ticketsSold);
        
        // Invalidate cache
        $this->cache->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId()->toString());
        $this->cache->deletePattern('events.*');
    }

    public function completeEvent(Event $event): void
    {
        // Domain service now handles validation internally
        $this->eventDomainService->completeEvent($event);
        
        // Invalidate cache
        $this->cache->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId()->toString());
        $this->cache->deletePattern('events.*');
    }

    public function getEventStatistics(string $eventId, ?string $from = null, ?string $to = null): array
    {
        $event = $this->getEventById($eventId);
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }
        $fromDt = $from ? new \DateTimeImmutable($from) : null;
        $toDt = $to ? new \DateTimeImmutable($to) : null;
        return $this->eventRepository->getEventStatistics($event, $fromDt, $toDt);
    }

    public function publishEvent(Event $event, User $publisher): void
    {
        if (!$this->eventDomainService->isEventPublishable($event)) {
            throw new \InvalidArgumentException('Event is not publishable');
        }

        $publishedAt = $this->eventPublishingService->publishEvent($event, $publisher);

        // Send notifications via NotificationApplicationService
        $this->notificationApplicationService->sendEventPublishedNotifications($event);

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
            fn() => $this->eventRepository->findByUuid($eventId) ?? $this->eventRepository->find($eventId),
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

}