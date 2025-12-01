<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Message\Command\Event\CancelEventCommand;
use App\Message\Command\Event\CreateEventCommand;
use App\Message\Command\Event\PublishEventCommand;
use App\Message\Command\Event\UnpublishEventCommand;
use App\Message\Command\Event\UpdateEventCommand;
use App\Message\Query\Event\GetEventByIdQuery;
use App\Message\Query\Event\GetEventStatisticsQuery;
use App\Message\Query\Event\GetEventsWithFiltersQuery;
use App\Message\Query\Event\GetFilterOptionsQuery;
use App\Application\Service\NotificationApplicationService;
use App\Presenter\EventPresenter;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\DTO\EventFiltersDTO;
use App\Exception\Event\EventNotFoundException;
use App\Exception\Event\EventNotPublishableException;
use App\Exception\Authorization\InsufficientPermissionsException;
use App\Exception\Validation\InvalidRequestDataException;
use App\Security\Voter\EventVoter;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Autowire(service: 'messenger.bus.query')] private readonly MessageBusInterface $queryBus,
        private readonly NotificationApplicationService $notificationApplicationService,
        private readonly RequestValidatorInterface $requestValidator,
        private readonly EventPresenter $eventPresenter
    ) {}

    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
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

        $envelope = $this->queryBus->dispatch(new GetEventsWithFiltersQuery(
            $filtersDTO->toArray(),
            $filtersDTO->getSorting(),
            $filtersDTO->page,
            $filtersDTO->limit
        ));
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'events' => array_map(fn($event) => $this->eventPresenter->presentListItem($event), $result['events']),
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'pages' => $result['pages']
            ]
        ]);
    }

    #[Route('/filters/options', name: 'api_events_filter_options', methods: ['GET'])]
    public function getFilterOptions(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetFilterOptionsQuery());
        $options = $envelope->last(HandledStamp::class)->getResult();
        return $this->json($options);
    }


    #[Route('/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();
        
        if (!$event) {
            throw new EventNotFoundException($id);
        }
        
        return $this->json($this->eventPresenter->presentDetails($event));
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function create(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $eventDTO = $this->requestValidator->validateAndCreateEventDTO($request);
        
        $envelope = $this->commandBus->dispatch(new CreateEventCommand(
            $eventDTO->name,
            $eventDTO->description,
            $eventDTO->eventDate,
            $eventDTO->venue,
            $eventDTO->maxTickets,
            $user->getId()->toString(),
            $eventDTO->ticketTypes
        ));
        $eventId = $envelope->last(HandledStamp::class)->getResult();

        $this->notificationApplicationService->sendGlobalNotification([
            'title' => 'Draft Event Created',
            'message' => "Draft event '{$eventDTO->name}' created by {$user->getEmail()}",
            'type' => 'info',
            'event_id' => $eventId,
            'timestamp' => (new \DateTime())->format('c')
        ]);

        return $this->json([
            'message' => 'Event created successfully',
            'event' => [
                'id' => $eventId,
                'name' => $eventDTO->name,
                'status' => Event::STATUS_DRAFT,
                'createdAt' => (new \DateTimeImmutable())->format('c')
            ]
        ], Response::HTTP_CREATED
        );
    }

    #[Route('/{id}/publish', name: 'api_events_publish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function publish(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::PUBLISH, $event);

        if ($event->getStatus() !== Event::STATUS_DRAFT) {
            throw new EventNotPublishableException($id, 'Only draft events can be published');
        }

        $this->commandBus->dispatch(new PublishEventCommand(
            $id,
            $user->getId()->toString()
        ));

        return $this->json([
            'message' => 'Event publication queued successfully',
            'event_id' => $id
        ], Response::HTTP_ACCEPTED);
    }


    #[Route('/{id}', name: 'api_events_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function update(
        string $id,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $eventDTO = $this->requestValidator->validateAndCreateEventDTO($request);

        $this->commandBus->dispatch(new UpdateEventCommand($id, $user->getId()->toString(), $eventDTO));

        $this->notificationApplicationService->sendGlobalNotification([
            'title' => 'Event Updated',
            'message' => "Event '{$eventDTO->name}' has been updated",
            'type' => 'info',
            'event_id' => $id,
            'timestamp' => (new \DateTime())->format('c')
        ]);

        return $this->json([
            'message' => 'Event updated successfully',
            'event' => [
                'id' => $id,
                'name' => $eventDTO->name,
                'description' => $eventDTO->description,
                'eventDate' => $eventDTO->eventDate->format('c'),
                'venue' => $eventDTO->venue,
                'maxTickets' => $eventDTO->maxTickets,
                'status' => $event->getStatus()
            ]
        ]);
    }

    #[Route('/{id}/cancel', name: 'api_events_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function cancel(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::CANCEL, $event);

        $this->commandBus->dispatch(new CancelEventCommand($id));

        $this->notificationApplicationService->sendGlobalNotification([
            'title' => 'Event Cancelled',
            'message' => "Event '{$event->getName()}' has been cancelled",
            'type' => 'warning',
            'event_id' => $event->getId()->toString(),
            'timestamp' => (new \DateTime())->format('c')
        ]);

        return $this->json(['message' => 'Event cancelled successfully', 'event' => $this->eventPresenter->presentDetails($event)]);
    }

    #[Route('/{id}/unpublish', name: 'api_events_unpublish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function unpublish(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::UNPUBLISH, $event);

        $this->commandBus->dispatch(new UnpublishEventCommand($id, $user->getId()->toString()));

        $this->notificationApplicationService->sendGlobalNotification([
            'title' => 'Event Unpublished',
            'message' => "Event '{$event->getName()}' has been unpublished",
            'type' => 'info',
            'event_id' => $event->getId()->toString(),
            'timestamp' => (new \DateTime())->format('c')
        ]);

        return $this->json(['message' => 'Event unpublished successfully']);
    }

    #[Route('/{id}/statistics', name: 'api_events_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function statistics(
        string $id,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::STATISTICS, $event);

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $fromDt = $from ? new \DateTimeImmutable($from) : null;
        $toDt = $to ? new \DateTimeImmutable($to) : null;

        $envelope = $this->queryBus->dispatch(new GetEventStatisticsQuery($id, $fromDt, $toDt));
        $statistics = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($statistics);
    }

    #[Route('/{id}/notify', name: 'api_events_notify', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function notify(
        string $id,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $envelope = $this->queryBus->dispatch(new GetEventByIdQuery($id));
        $event = $envelope->last(HandledStamp::class)->getResult();

        if (!$event) {
            throw new EventNotFoundException($id);
        }

        $this->denyAccessUnlessGranted(EventVoter::NOTIFY, $event);

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \App\Exception\Validation\ValidationException(['json' => 'Invalid JSON']);
        }
        if (!isset($data['message'])) {
            throw new \App\Exception\Validation\ValidationException(['message' => 'required']);
        }

        $attendees = $event->getAttendees();
        foreach ($attendees as $attendee) {
            $this->notificationApplicationService->sendNotificationToUser($attendee->getId()->toString(), [
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
    }
}