<?php

namespace App\Presenter;

use App\DTO\EventOutput;
use App\Entity\Event;

final class EventPresenter
{
    public function present(Event $event): EventOutput
    {
        $out = new EventOutput();
        $out->id = $event->getId()->toString();
        $out->name = $event->getName();
        $out->description = $event->getDescription();
        $out->eventDate = $event->getEventDate()->format('c');
        $out->venue = $event->getVenue();
        $out->maxTickets = $event->getMaxTickets();
        $out->status = $event->getStatus();
        $out->publishedAt = $event->getPublishedAt()?->format('c');
        $out->createdAt = $event->getCreatedAt()->format('c');
        $out->ticketsSold = $event->getTicketsSold();
        $out->availableTickets = $event->getAvailableTickets();
        return $out;
    }
}
