<?php

namespace App\Presenter;

use App\Contract\Presentation\EventPresenterInterface;
use App\Domain\Event\Service\EventCalculationService;
use App\Domain\Event\Service\EventDomainService;
use App\DTO\EventResponseDTO;
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
        $out = new EventResponseDTO();
        $out->id = $event->getId()?->toString();
        $out->name = $event->getName();
        $out->description = $event->getDescription();
        $out->eventDate = EventDate::fromNative($event->getEventDate())->format();
        $out->venue = $event->getVenue();
        $out->maxTickets = $event->getMaxTickets();
        $out->status = $event->getStatus();
        $out->publishedAt = $event->getPublishedAt()?->format('c');
        $out->createdAt = $event->getCreatedAt()?->format('c');
        $out->ticketsSold = $this->calculationService->calculateTicketsSold($event);
        $out->availableTickets = $this->calculationService->calculateAvailableTickets($event);
        
        return $out;
    }

    public function presentListItem(Event $event): array
    {
        return [
            'id' => $event->getId()?->toString(),
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'eventDate' => EventDate::fromNative($event->getEventDate())->format(),
            'eventDateFormatted' => $this->formatEventDate($event),
            'venue' => $event->getVenue(),
            'maxTickets' => $event->getMaxTickets(),
            'status' => $event->getStatus(),
            'statusLabel' => $this->getStatusLabel($event),
            'publishedAt' => $event->getPublishedAt()?->format('c'),
            'createdAt' => $event->getCreatedAt()?->format('c'),
            'createdAtFormatted' => $this->formatCreatedAt($event),
            'organizer' => [
                'id' => $event->getOrganizer()?->getId()?->toString(),
                'name' => $event->getOrganizer()?->getFullName(),
            ],
            'ticketTypes' => array_map(
                fn($tt) => [
                    'id' => $tt->getId()?->toString(),
                    'name' => $tt->getName(),
                    'price' => $tt->getPrice(),
                    'quantity' => $tt->getQuantity(),
                    'priceFormatted' => Money::fromInt($tt->getPrice())->format(),
                ],
                $event->getTicketTypes()->toArray()
            ),
        ];
    }

    public function presentDetails(Event $event): array
    {
        $listItem = $this->presentListItem($event);
        $ticketsSold = $this->calculationService->calculateTicketsSold($event);
        
        return array_merge($listItem, [
            'ticketsSold' => $ticketsSold,
            'availableTickets' => $this->calculationService->calculateAvailableTickets($event),
            'totalRevenue' => $this->calculationService->calculateTotalRevenue($event),
            'occupancyRate' => $this->calculationService->getOccupancyRate($event),
            'attendeesCount' => $this->calculationService->getAttendeesCount($event),
            'ordersCount' => $this->calculationService->getOrdersCount($event),
            'daysUntilEvent' => $this->calculationService->getDaysUntilEvent($event),
            'isUpcoming' => $this->domainService->isUpcoming($event),
            'isPast' => $this->domainService->isPast($event),
            'isSoldOut' => $this->domainService->isSoldOut($event, $ticketsSold),
            'canBeModified' => $this->domainService->canBeModified($event, $ticketsSold),
            'canBeCancelled' => $this->domainService->canBeCancelled($event),
            'canBePublished' => $this->domainService->canBePublished($event),
            'canBeCompleted' => $this->domainService->canBeCompleted($event),
        ]);
    }

    public function getStatusLabel(Event $event): string
    {
        return match($event->getStatus()) {
            Event::STATUS_DRAFT => 'Draft',
            Event::STATUS_PUBLISHED => 'Published',
            Event::STATUS_CANCELLED => 'Cancelled',
            Event::STATUS_COMPLETED => 'Completed',
            default => 'Unknown'
        };
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
