<?php

namespace App\Presenter;

use App\Contract\Presentation\EventPresenterInterface;

use App\DTO\EventOutput;
use App\Entity\Event;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\EventDate;

final class EventPresenter implements \App\Contract\Presentation\EventPresenterInterface
{
    public function present(Event $event): EventOutput
    {
        $out = new EventOutput();
        $out->id = $event->getId()?->toString();
        $out->name = $event->getName();
        $out->description = $event->getDescription();
        $out->eventDate = EventDate::fromNative($event->getEventDate())->format();
        $out->venue = $event->getVenue();
        $out->maxTickets = $event->getMaxTickets();
        $out->status = $event->getStatus();
        $out->publishedAt = $event->getPublishedAt()?->format('c');
        $out->createdAt = $event->getCreatedAt()?->format('c');
        // Optional counters if available on entity
        if (method_exists($event, 'getTicketsSold')) { $out->ticketsSold = $event->getTicketsSold(); }
        if (method_exists($event, 'getAvailableTickets')) { $out->availableTickets = $event->getAvailableTickets(); }
        return $out;
    }

    public function presentListItem(Event $event): array
    {
        return [
            'id' => $event->getId()?->toString(),
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'eventDate' => EventDate::fromNative($event->getEventDate())->format(),
            'venue' => $event->getVenue(),
            'maxTickets' => $event->getMaxTickets(),
            'status' => $event->getStatus(),
            'publishedAt' => $event->getPublishedAt()?->format('c'),
            'createdAt' => $event->getCreatedAt()?->format('c'),
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
        return $this->presentListItem($event);
    }
}
