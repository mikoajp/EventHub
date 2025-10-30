<?php

namespace App\Presenter;

use App\Contract\Presentation\EventPresenterInterface;
use App\Domain\Event\Service\EventCalculationService;
use App\Domain\Event\Service\EventDomainService;
use App\DTO\EventResponseDTO;
use App\DTO\EventListItemDTO;
use App\DTO\EventDetailsDTO;
use App\Entity\Event;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\EventDate;

final readonly class EventPresenter implements EventPresenterInterface
{
    public function __construct(
        private EventCalculationService $calculationService,
        private EventDomainService $domainService
    ) {}

    public function present(Event $event): EventResponseDTO
    {
        return new EventResponseDTO(
            id: $event->getId()?->toString(),
            name: $event->getName(),
            description: $event->getDescription(),
            eventDate: EventDate::fromNative($event->getEventDate())->format(),
            venue: $event->getVenue(),
            maxTickets: $event->getMaxTickets(),
            status: $event->getStatus()->value,
            publishedAt: $event->getPublishedAt()?->format('c'),
            createdAt: $event->getCreatedAt()?->format('c'),
            ticketsSold: $this->calculationService->calculateTicketsSold($event),
            availableTickets: $this->calculationService->calculateAvailableTickets($event),
        );
    }

    public function presentListItem(Event $event): array
    {
        $dto = $this->presentListItemDto($event);
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'description' => $dto->description,
            'eventDate' => $dto->eventDate,
            'eventDateFormatted' => $dto->eventDateFormatted,
            'venue' => $dto->venue,
            'maxTickets' => $dto->maxTickets,
            'status' => $dto->status,
            'statusLabel' => $dto->statusLabel,
            'publishedAt' => $dto->publishedAt,
            'createdAt' => $dto->createdAt,
            'createdAtFormatted' => $dto->createdAtFormatted,
            'organizer' => $dto->organizer,
            'ticketTypes' => $dto->ticketTypes,
        ];
    }

    public function presentListItemDto(Event $event): EventListItemDTO
    {
        return new EventListItemDTO(
            id: $event->getId()?->toString(),
            name: $event->getName(),
            description: $event->getDescription(),
            eventDate: EventDate::fromNative($event->getEventDate())->format(),
            eventDateFormatted: $this->formatEventDate($event),
            venue: $event->getVenue(),
            maxTickets: $event->getMaxTickets(),
            status: $event->getStatus()->value,
            statusLabel: $this->getStatusLabel($event),
            publishedAt: $event->getPublishedAt()?->format('c'),
            createdAt: $event->getCreatedAt()?->format('c'),
            createdAtFormatted: $this->formatCreatedAt($event),
            organizer: [
                'id' => $event->getOrganizer()?->getId()->toString(),
                'name' => $event->getOrganizer()?->getFullName(),
            ],
            ticketTypes: array_map(
                fn($tt) => [
                    'id' => $tt->getId()->toString(),
                    'name' => $tt->getName(),
                    'price' => $tt->getPrice(),
                    'quantity' => $tt->getQuantity(),
                    'priceFormatted' => Money::fromInt($tt->getPrice())->format(),
                ],
                $event->getTicketTypes()->toArray()
            ),
        );
    }

    public function presentDetails(Event $event): array
    {
        $dto = $this->presentDetailsDto($event);
        $base = $this->presentListItem($event);
        return array_merge($base, [
            'ticketsSold' => $dto->ticketsSold,
            'availableTickets' => $dto->availableTickets,
            'totalRevenue' => $dto->totalRevenue,
            'occupancyRate' => $dto->occupancyRate,
            'attendeesCount' => $dto->attendeesCount,
            'ordersCount' => $dto->ordersCount,
            'daysUntilEvent' => $dto->daysUntilEvent,
            'isUpcoming' => $dto->isUpcoming,
            'isPast' => $dto->isPast,
            'isSoldOut' => $dto->isSoldOut,
            'canBeModified' => $dto->canBeModified,
            'canBeCancelled' => $dto->canBeCancelled,
            'canBePublished' => $dto->canBePublished,
            'canBeCompleted' => $dto->canBeCompleted,
        ]);
    }

    public function presentDetailsDto(Event $event): EventDetailsDTO
    {
        $ticketsSold = $this->calculationService->calculateTicketsSold($event);
        $base = $this->presentListItemDto($event);
        return new EventDetailsDTO(
            id: $base->id,
            name: $base->name,
            description: $base->description,
            eventDate: $base->eventDate,
            eventDateFormatted: $base->eventDateFormatted,
            venue: $base->venue,
            maxTickets: $base->maxTickets,
            status: $base->status,
            statusLabel: $base->statusLabel,
            publishedAt: $base->publishedAt,
            createdAt: $base->createdAt,
            createdAtFormatted: $base->createdAtFormatted,
            organizer: $base->organizer,
            ticketTypes: $base->ticketTypes,
            ticketsSold: $ticketsSold,
            availableTickets: $this->calculationService->calculateAvailableTickets($event),
            totalRevenue: $this->calculationService->calculateTotalRevenue($event),
            occupancyRate: $this->calculationService->getOccupancyRate($event),
            attendeesCount: $this->calculationService->getAttendeesCount($event),
            ordersCount: $this->calculationService->getOrdersCount($event),
            daysUntilEvent: $this->calculationService->getDaysUntilEvent($event),
            isUpcoming: $this->domainService->isUpcoming($event),
            isPast: $this->domainService->isPast($event),
            isSoldOut: $this->domainService->isSoldOut($event, $ticketsSold),
            canBeModified: $this->domainService->canBeModified($event, $ticketsSold),
            canBeCancelled: $this->domainService->canBeCancelled($event),
            canBePublished: $this->domainService->canBePublished($event),
            canBeCompleted: $this->domainService->canBeCompleted($event),
        );
    }

    public function getStatusLabel(Event $event): string
    {
        return $event->getStatus()->getLabel();
    }

    public function formatEventDate(Event $event): string
    {
        return $event->getEventDate()?->format('M j, Y \a\t g:i A') ?? '';
    }

    public function formatCreatedAt(Event $event): string
    {
        return $event->getCreatedAt()?->format('M j, Y \a\t g:i A') ?? '';
    }
}
