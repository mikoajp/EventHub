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
        private EventRepository $eventRepository,
        private CacheInterface $cache
    ) {}

    /**
     * @deprecated Use CreateEventCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function createEvent(EventDTO $eventDTO, User $organizer): Event
    {
        throw new \LogicException(
            'This method is deprecated. Use CreateEventCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new CreateEventCommand(...))'
        );
    }

    /**
     * @deprecated Use UpdateEventCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function updateEvent(Event $event, EventDTO $eventDTO, User $user): Event
    {
        throw new \LogicException(
            'This method is deprecated. Use UpdateEventCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new UpdateEventCommand(...))'
        );
    }

    /**
     * @deprecated Use CancelEventCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function cancelEvent(Event $event): void
    {
        throw new \LogicException(
            'This method is deprecated. Use CancelEventCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new CancelEventCommand(...))'
        );
    }

    /**
     * @deprecated Use UnpublishEventCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function unpublishEvent(Event $event): void
    {
        throw new \LogicException(
            'This method is deprecated. Use UnpublishEventCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new UnpublishEventCommand(...))'
        );
    }

    /**
     * @deprecated Consider creating CompleteEventCommand
     * This method is kept for backward compatibility only.
     */
    public function completeEvent(Event $event): void
    {
        throw new \LogicException(
            'This method is deprecated. Consider creating CompleteEventCommand.'
        );
    }

    /**
     * @deprecated Use GetEventStatisticsQuery with query bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
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

    /**
     * @deprecated Use PublishEventCommand with command bus instead
     * This method is kept for backward compatibility only.
     * Will be removed in next major version.
     */
    public function publishEvent(Event $event, User $publisher): void
    {
        throw new \LogicException(
            'This method is deprecated. Use PublishEventCommand with command bus instead. ' .
            'Example: $this->commandBus->dispatch(new PublishEventCommand(...))'
        );
    }

    /**
     * @deprecated Use GetEventsWithFiltersQuery for published events
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
    public function getPublishedEvents(): array
    {
        return $this->cache->get(
            self::CACHE_KEY_PUBLISHED_EVENTS,
            fn() => $this->eventRepository->findPublishedEvents(),
            self::CACHE_TTL_PUBLISHED_EVENTS
        );
    }

    /**
     * @deprecated Use GetEventByIdQuery with query bus instead
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
    public function getEventById(string $eventId): ?Event
    {
        return $this->cache->get(
            self::CACHE_KEY_EVENT_PREFIX . $eventId,
            fn() => $this->eventRepository->findByUuid($eventId) ?? $this->eventRepository->find($eventId),
            self::CACHE_TTL_SINGLE_EVENT
        );
    }

    /**
     * @deprecated Use GetEventsWithFiltersQuery with query bus instead
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
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

    /**
     * @deprecated Use GetFilterOptionsQuery with query bus instead
     * This method is kept for backward compatibility.
     * Will be removed in next major version.
     */
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