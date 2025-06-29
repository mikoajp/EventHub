<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Message\Command\Event\PublishEventCommand;
use App\Service\EventService;
use App\Service\NotificationService;
use App\Service\ErrorHandlerService;
use App\Service\ValidationService;
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
        private readonly EventService        $eventService,
        private readonly NotificationService $notificationService,
        private readonly ErrorHandlerService $errorHandler,
        private readonly ValidationService   $validationService
    ) {}

    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $events = $this->eventService->getPublishedEvents();
            return $this->json($this->eventService->formatEventsCollectionResponse($events));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch events');
        }
    }

    #[Route('/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $event = $this->eventService->findEventOrFail($id);
            return $this->json($this->eventService->formatSingleEventResponse($event));
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
            $this->eventService->validateUser($user);

            $eventDTO = $this->validationService->validateAndCreateEventDTO($request);
            $event = $this->eventService->createEventFromDTO($eventDTO, $user);

            $this->notificationService->sendRealTimeUpdate('events/draft_created', [
                'event_id' => $event->getId()->toString(),
                'event_name' => $event->getName(),
                'organizer' => $user->getEmail(),
                'message' => "Draft event '{$event->getName()}' created by {$user->getEmail()}",
                'timestamp' => (new \DateTime())->format('c')
            ]);

            return $this->json(
                $this->eventService->formatEventCreationResponse($event),
                Response::HTTP_CREATED
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
            $event = $this->eventService->findEventOrFail($id);
            $this->eventService->validateUserCanPublishEvent($event, $user);

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
            $this->eventService->validateUser($user);

            $event = $this->eventService->findEventOrFail($id);

            if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied', Response::HTTP_FORBIDDEN);
            }

            $eventDTO = $this->validationService->validateAndCreateEventDTO($request);
            $updatedEvent = $this->eventService->updateEventFromDTO($event, $eventDTO);

            $this->notificationService->notifyEventUpdated($updatedEvent);

            return $this->json([
                'message' => 'Event updated successfully',
                'event' => $this->eventService->formatSingleEventResponse($updatedEvent)
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