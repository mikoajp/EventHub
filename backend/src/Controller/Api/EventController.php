<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Message\Command\Event\PublishEventCommand;
use App\Application\Service\EventApplicationService;
use App\Application\Service\NotificationApplicationService;
use App\Service\ErrorHandlerService;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\DTO\EventFiltersDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly EventApplicationService $eventApplicationService,
        private readonly NotificationApplicationService $notificationApplicationService,
        private readonly ErrorHandlerService $errorHandler,
        private readonly RequestValidatorInterface $requestValidator
    ) {}

    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $filtersDTO = new EventFiltersDTO(
                search: $request->query->get('search'),
                status: $request->query->all('status') ?: ['published'],
                venue: $request->query->all('venue') ?: [],
                organizer_id: $request->query->get('organizer_id'),
                date_from: $request->query->get('date_from'),
                date_to: $request->query->get('date_to'),
                price_min: $request->query->get('price_min') ? (float)$request->query->get('price_min') : null,
                price_max: $request->query->get('price_max') ? (float)$request->query->get('price_max') : null,
                has_available_tickets: $request->query->getBoolean('has_available_tickets', false),
                sort_by: $request->query->get('sort_by', 'date'),
                sort_direction: $request->query->get('sort_direction', 'asc'),
                page: max(1, $request->query->getInt('page', 1)),
                limit: min(100, max(1, $request->query->getInt('limit', 20)))
            );

            $violations = $this->requestValidator->validate($filtersDTO);
            if (count($violations) > 0) {
                return $this->json(['errors' => $violations], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->eventApplicationService->getEventsWithFilters(
                $filtersDTO->toArray(),
                $filtersDTO->getSorting(),
                $filtersDTO->page,
                $filtersDTO->limit
            );

            return $this->json([
                'events' => array_map(fn($event) => $this->formatEventResponse($event), $result['events']),
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'pages' => $result['pages']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch events');
        }
    }

    #[Route('/filter-options', name: 'api_events_filter_options', methods: ['GET'])]
    public function getFilterOptions(): JsonResponse
    {
        try {
            $options = $this->eventApplicationService->getFilterOptions();
            return $this->json($options);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch filter options');
        }
    }

    private function formatEventResponse($event): array
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
            'createdAt' => $event->getCreatedAt()->format('c'),
            'organizer' => [
                'id' => $event->getOrganizer()->getId()->toString(),
                'name' => $event->getOrganizer()->getFullName()
            ],
            'ticketTypes' => array_map(fn($ticketType) => [
                'id' => $ticketType->getId()->toString(),
                'name' => $ticketType->getName(),
                'price' => $ticketType->getPrice(),
                'quantity' => $ticketType->getQuantity(),
                'priceFormatted' => number_format($ticketType->getPrice() / 100, 2)
            ], $event->getTicketTypes()->toArray())
        ];
    }

    #[Route('/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $event = $this->eventApplicationService->getEventById($id);
            if (!$event) {
                throw new \InvalidArgumentException('Event not found', Response::HTTP_NOT_FOUND);
            }
            
            return $this->json([
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
                'createdAt' => $event->getCreatedAt()->format('c'),
                'organizer' => [
                    'id' => $event->getOrganizer()->getId()->toString(),
                    'name' => $event->getOrganizer()->getFullName(),
                    'email' => $event->getOrganizer()->getEmail()
                ],
                'ticketTypes' => array_map(fn($ticketType) => [
                    'id' => $ticketType->getId()->toString(),
                    'name' => $ticketType->getName(),
                    'price' => $ticketType->getPrice(),
                    'quantity' => $ticketType->getQuantity()
                ], $event->getTicketTypes()->toArray())
            ]);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch event');
        }
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function create(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                throw new \RuntimeException('User not authenticated', Response::HTTP_UNAUTHORIZED);
            }

            $eventDTO = $this->requestValidator->validateAndCreateEventDTO($request);
            $event = $this->eventApplicationService->createEvent($eventDTO, $user);

            $this->notificationApplicationService->sendGlobalNotification([
                'title' => 'Draft Event Created',
                'message' => "Draft event '{$event->getName()}' created by {$user->getEmail()}",
                'type' => 'info',
                'event_id' => $event->getId()->toString(),
                'timestamp' => (new \DateTime())->format('c')
            ]);

            return $this->json([
                'message' => 'Event created successfully',
                'event' => [
                    'id' => $event->getId()->toString(),
                    'name' => $event->getName(),
                    'status' => $event->getStatus(),
                    'createdAt' => $event->getCreatedAt()->format('c')
                ]
            ], Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to create event');
        }
    }

    #[Route('/{id}/publish', name: 'api_events_publish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function publish(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                throw new \RuntimeException('User not authenticated', Response::HTTP_UNAUTHORIZED);
            }

            $event = $this->eventApplicationService->getEventById($id);
            if (!$event) {
                throw new \InvalidArgumentException('Event not found', Response::HTTP_NOT_FOUND);
            }

            if ($event->getStatus() !== Event::STATUS_DRAFT) {
                throw new \InvalidArgumentException('Only draft events can be published');
            }

            $this->commandBus->dispatch(new PublishEventCommand(
                $id,
                $user->getId()->toString()
            ));

            return $this->json([
                'message' => 'Event publication queued successfully',
                'event_id' => $id
            ], Response::HTTP_ACCEPTED);

        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to queue event publication');
        }
    }


    #[Route('/{id}', name: 'api_events_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function update(
        string $id,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                throw new \RuntimeException('User not authenticated', Response::HTTP_UNAUTHORIZED);
            }

            $event = $this->eventApplicationService->getEventById($id);
            if (!$event) {
                throw new \InvalidArgumentException('Event not found', Response::HTTP_NOT_FOUND);
            }

            $eventDTO = $this->requestValidator->validateAndCreateEventDTO($request);
            $updatedEvent = $this->eventApplicationService->updateEvent($event, $eventDTO, $user);

            $this->notificationApplicationService->sendGlobalNotification([
                'title' => 'Event Updated',
                'message' => "Event '{$updatedEvent->getName()}' has been updated",
                'type' => 'info',
                'event_id' => $updatedEvent->getId()->toString(),
                'timestamp' => (new \DateTime())->format('c')
            ]);

            return $this->json([
                'message' => 'Event updated successfully',
                'event' => [
                    'id' => $updatedEvent->getId()->toString(),
                    'name' => $updatedEvent->getName(),
                    'description' => $updatedEvent->getDescription(),
                    'eventDate' => $updatedEvent->getEventDate()->format('c'),
                    'venue' => $updatedEvent->getVenue(),
                    'maxAttendees' => $updatedEvent->getMaxAttendees(),
                    'status' => $updatedEvent->getStatus()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to update event');
        }
    }

    #[Route('/{id}/cancel', name: 'api_events_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function cancel(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            $this->eventService->validateUser($user);

            $event = $this->eventService->findEventOrFail($id);

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
            }

            $this->eventService->cancelEvent($event);

            $this->notificationService->notifyEventCancelled($event);

            return $this->json([
                'message' => 'Event cancelled successfully',
                'event' => $this->eventService->formatSingleEventResponse($event)
            ]);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to cancel event');
        }
    }

    #[Route('/{id}/unpublish', name: 'api_events_unpublish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function unpublish(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            $this->eventService->validateUser($user);

            $event = $this->eventService->findEventOrFail($id);

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
            }

            $this->eventService->unpublishEvent($event);

            $this->notificationService->sendRealTimeUpdate('events/unpublished', [
                'event_id' => $event->getId()->toString(),
                'event_name' => $event->getName(),
                'message' => "Event '{$event->getName()}' has been unpublished",
                'timestamp' => (new \DateTime())->format('c')
            ]);

            return $this->json(['message' => 'Event unpublished successfully']);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to unpublish event');
        }
    }

    #[Route('/{id}/statistics', name: 'api_events_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function statistics(
        string $id,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            $this->eventService->validateUser($user);

            $event = $this->eventService->findEventOrFail($id);

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
            }

            $from = $request->query->get('from');
            $to = $request->query->get('to');

            $statistics = $this->eventService->getEventStatistics($id, $from, $to);

            return $this->json($statistics);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch event statistics');
        }
    }

    #[Route('/{id}/notify', name: 'api_events_notify', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function notify(
        string $id,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            $this->eventService->validateUser($user);

            $event = $this->eventService->findEventOrFail($id);

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['message'])) {
                throw new \InvalidArgumentException('Message is required');
            }

            $attendees = $event->getAttendees();
            foreach ($attendees as $attendee) {
                $this->notificationService->publishNotificationToUser($attendee->getId()->toString(), [
                    'title' => $data['title'] ?? 'Event Update',
                    'message' => $data['message'],
                    'type' => $data['type'] ?? 'info',
                    'event_id' => $event->getId()->toString()
                ]);
            }

            return $this->json([
                'message' => 'Notification sent successfully',
                'recipients' => count($attendees)
            ]);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to send notification');
        }
    }
}