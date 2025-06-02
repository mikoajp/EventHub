<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EventService
{
    private const CACHE_TTL_PUBLISHED_EVENTS = 1800; // 30 minutes
    private const CACHE_TTL_SINGLE_EVENT = 3600; // 1 hour
    private const CACHE_TTL_STATISTICS = 300; // 5 minutes for statistics
    private const CACHE_KEY_PUBLISHED_EVENTS = 'events.published';
    private const CACHE_KEY_EVENT_PREFIX = 'event.';
    private const CACHE_KEY_STATISTICS_PREFIX = 'event.statistics.';

    public function __construct(
        private readonly EventRepository        $eventRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheService           $cacheService
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function getPublishedEvents(): array
    {
        return $this->cacheService->get(self::CACHE_KEY_PUBLISHED_EVENTS, function() {
            return $this->eventRepository->findBy(['status' => Event::STATUS_PUBLISHED]);
        }, self::CACHE_TTL_PUBLISHED_EVENTS);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findEventOrFail(string $id): Event
    {
        $cacheKey = self::CACHE_KEY_EVENT_PREFIX . $id;

        return $this->cacheService->get($cacheKey, function() use ($id) {
            $event = $this->eventRepository->findByUuid($id);
            if (!$event) {
                throw new \RuntimeException('Event not found', Response::HTTP_NOT_FOUND);
            }
            return $event;
        }, self::CACHE_TTL_SINGLE_EVENT);
    }

    /**
     * @throws DateMalformedStringException
     * @throws InvalidArgumentException|Exception
     */
    public function getEventStatistics(string $eventId, ?string $from = null, ?string $to = null): array
    {
        $cacheKey = self::CACHE_KEY_STATISTICS_PREFIX . $eventId . '_' . ($from ?? '') . '_' . ($to ?? '');

        return $this->cacheService->get($cacheKey, function() use ($eventId, $from, $to) {
            $event = $this->findEventOrFail($eventId);

            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate = $to ? new DateTimeImmutable($to) : null;

            $statistics = $this->eventRepository->getEventStatistics($event, $fromDate, $toDate);

            $conversionRate = $this->calculateConversionRate($event, $fromDate, $toDate);

            return [
                'eventId' => $eventId,
                'period' => [
                    'from' => $from,
                    'to' => $to
                ],
                'summary' => [
                    'totalTicketsSold' => $statistics['sold_tickets'],
                    'totalRevenue' => $statistics['total_revenue'],
                    'totalOrders' => $statistics['total_orders'],
                    'averageOrderValue' => $statistics['average_order_value'],
                    'conversionRate' => $conversionRate
                ],
                'ticketTypes' => $statistics['sales_by_type'],
                'revenue' => $statistics['revenue_data'],
                'orders' => $statistics['order_data'],
                'dailyBreakdown' => $statistics['daily_breakdown'],
                'generatedAt' => (new \DateTime())->format('c')
            ];
        }, self::CACHE_TTL_STATISTICS);
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

        $this->invalidateEventCache($event);

        return $event;
    }

    public function updateEventFromDTO(Event $event, EventDTO $eventDTO): Event
    {
        $event->setName($eventDTO->name)
            ->setDescription($eventDTO->description)
            ->setEventDate($eventDTO->eventDate)
            ->setVenue($eventDTO->venue)
            ->setMaxTickets($eventDTO->maxTickets);

        if ($event->getStatus() === Event::STATUS_PUBLISHED && $event->getTicketsSold() === 0) {
            $event->setStatus(Event::STATUS_DRAFT);
        }

        $this->entityManager->flush();
        $this->invalidateEventCache($event);

        return $event;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function publishEvent(string $id, User $user): Event
    {
        $event = $this->findEventOrFail($id);

        if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
        }

        if ($event->getStatus() === Event::STATUS_CANCELLED) {
            throw new \RuntimeException('Cannot publish a cancelled event', Response::HTTP_BAD_REQUEST);
        }

        $event->setStatus(Event::STATUS_PUBLISHED)
            ->setPublishedAt(new \DateTime());

        $this->entityManager->flush();
        $this->invalidateEventCache($event);

        return $event;
    }

    public function unpublishEvent(Event $event): void
    {
        if ($event->getStatus() !== Event::STATUS_PUBLISHED) {
            throw new \RuntimeException('Only published events can be unpublished', Response::HTTP_BAD_REQUEST);
        }

        if ($event->getTicketsSold() > 0) {
            throw new \RuntimeException('Cannot unpublish event with sold tickets', Response::HTTP_BAD_REQUEST);
        }

        $event->setStatus(Event::STATUS_DRAFT)
            ->setPublishedAt(null);

        $this->entityManager->flush();
        $this->invalidateEventCache($event);
    }

    public function cancelEvent(Event $event): void
    {
        if ($event->getStatus() === Event::STATUS_CANCELLED) {
            throw new \RuntimeException('Event is already cancelled', Response::HTTP_BAD_REQUEST);
        }

        $previousStatus = $event->getStatus();
        $event->setStatus(Event::STATUS_CANCELLED)
            ->setCancelledAt(new \DateTime())
            ->setPreviousStatus($previousStatus);

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
            'publishedAt' => $event->getPublishedAt()?->format('c'),
            'cancelledAt' => $event->getCancelledAt()?->format('c'),
            'createdAt' => $event->getCreatedAt()->format('c'),
            'updatedAt' => $event->getUpdatedAt()->format('c'),
            'organizer' => $this->formatOrganizerData($event->getOrganizer()),
            'ticketTypes' => array_map([$this, 'formatTicketTypeData'], $event->getTicketTypes()->toArray()),
            'attendeesCount' => $event->getAttendees()->count(),
        ];
    }

    private function formatOrganizerData(User $organizer): array
    {
        return [
            'id' => $organizer->getId()->toString(),
            'fullName' => $organizer->getFullName(),
            'email' => $organizer->getEmail(),
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

    public function canUserModifyEvent(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    public function canEventBeModified(Event $event): bool
    {
        return $event->getStatus() !== Event::STATUS_CANCELLED &&
            ($event->getStatus() !== Event::STATUS_PUBLISHED || $event->getTicketsSold() === 0);
    }

    public function canEventBeCancelled(Event $event): bool
    {
        return $event->getStatus() !== Event::STATUS_CANCELLED;
    }

    public function canEventBePublished(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_DRAFT &&
            $event->getEventDate() > new \DateTime();
    }

    /**
     * Business logic for calculating conversion rate
     * This stays in service as it's business logic, not data access
     */
    private function calculateConversionRate(Event $event, ?\DateTimeImmutable $fromDate, ?\DateTimeImmutable $toDate): float
    {
        // This would require tracking page views or event views
        // For now, return a placeholder or calculate based on available data

        // Example: if we had view tracking, we could calculate:
        // $views = $this->getEventViews($event, $fromDate, $toDate);
        // $orders = $this->eventRepository->getOrderStatistics($event, $fromDate, $toDate);
        // return $views > 0 ? ($orders['totalOrders'] / $views) * 100 : 0.0;

        return 0.0;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function invalidateEventCache(Event $event): void
    {
        $this->cacheService->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId());
        $this->cacheService->delete(self::CACHE_KEY_PUBLISHED_EVENTS);

        $pattern = self::CACHE_KEY_STATISTICS_PREFIX . $event->getId() . '_*';
        $this->cacheService->deletePattern($pattern);
    }
}