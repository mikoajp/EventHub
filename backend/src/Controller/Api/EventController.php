<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Service\EventService;
use App\Service\ErrorHandlerService;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private ErrorHandlerService $errorHandler,
        private ValidationService $validationService
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
            $this->eventService->publishEvent($id, $user);
            return $this->json(['message' => 'Event published successfully']);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to publish event');
        }
    }
}