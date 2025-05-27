<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
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
            $event = $this->eventRepository->findByUuid($id);
            if (!$event) {
                throw new \RuntimeException('Event not found', Response::HTTP_NOT_FOUND);
            }
            return $event;
        }, self::CACHE_TTL_SINGLE_EVENT);
    }

    public function getEventStatistics(string $eventId, ?string $from = null, ?string $to = null): array
    {
        $cacheKey = self::CACHE_KEY_STATISTICS_PREFIX . $eventId . '_' . ($from ?? '') . '_' . ($to ?? '');

        return $this->cacheService->get($cacheKey, function() use ($eventId, $from, $to) {
            $event = $this->findEventOrFail($eventId);

            $fromDate = $from ? new \DateTimeImmutable($from) : null;
            $toDate = $to ? new \DateTimeImmutable($to) : null;

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

    private function invalidateEventCache(Event $event): void
    {
        $this->cacheService->delete(self::CACHE_KEY_EVENT_PREFIX . $event->getId());
        $this->cacheService->delete(self::CACHE_KEY_PUBLISHED_EVENTS);

        $pattern = self::CACHE_KEY_STATISTICS_PREFIX . $event->getId() . '_*';
        $this->cacheService->deletePattern($pattern);
    }
}