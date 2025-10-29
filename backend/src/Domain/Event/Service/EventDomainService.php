<?php

namespace App\Domain\Event\Service;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use Doctrine\ORM\EntityManagerInterface;

class EventDomainService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function createEvent(EventDTO $eventDTO, User $organizer): Event
    {
        $event = new Event();
        $event->setName($eventDTO->name)
            ->setDescription($eventDTO->description)
            ->setEventDate($eventDTO->eventDate)
            ->setVenue($eventDTO->venue)
            ->setMaxTickets($eventDTO->maxTickets)
            ->setOrganizer($organizer)
            ->setStatus(\App\Enum\EventStatus::DRAFT);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function updateEvent(Event $event, EventDTO $eventDTO): Event
    {
        $event->setName($eventDTO->name)
            ->setDescription($eventDTO->description)
            ->setEventDate($eventDTO->eventDate)
            ->setVenue($eventDTO->venue)
            ->setMaxTickets($eventDTO->maxTickets);

        $this->entityManager->flush();

        return $event;
    }

    public function canUserModifyEvent(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || 
               $user->hasRole(\App\Enum\UserRole::ADMIN);
    }

    public function canBeModified(Event $event, int $ticketsSold = 0): bool
    {
        return !$this->isCancelled($event) 
            && !$this->isCompleted($event)
            && (!$this->isPublished($event) || $ticketsSold === 0);
    }

    public function canBeCancelled(Event $event): bool
    {
        return !$this->isCancelled($event) && !$this->isCompleted($event);
    }

    public function canBePublished(Event $event): bool
    {
        return $this->isDraft($event) 
            && $event->getEventDate() > new \DateTime();
    }

    public function canBeUnpublished(Event $event, int $ticketsSold = 0): bool
    {
        return $this->isPublished($event) && $ticketsSold === 0;
    }

    public function canBeCompleted(Event $event): bool
    {
        return $this->isPublished($event) 
            && $event->getEventDate() < new \DateTime();
    }

    public function isEventPublishable(Event $event): bool
    {
        return $event->isDraft() &&
               $event->getName() &&
               $event->getEventDate() &&
               $event->getVenue();
    }

    public function isPublished(Event $event): bool
    {
        return $event->isPublished();
    }

    public function isDraft(Event $event): bool
    {
        return $event->isDraft();
    }

    public function isCancelled(Event $event): bool
    {
        return $event->isCancelled();
    }

    public function isCompleted(Event $event): bool
    {
        return $event->isCompleted();
    }

    public function isUpcoming(Event $event): bool
    {
        return $event->getEventDate() > new \DateTime();
    }

    public function isPast(Event $event): bool
    {
        return $event->getEventDate() < new \DateTime();
    }

    public function isSoldOut(Event $event, int $ticketsSold): bool
    {
        return ($event->getMaxTickets() - $ticketsSold) <= 0;
    }

    public function hasTicketsSold(int $ticketsSold): bool
    {
        return $ticketsSold > 0;
    }

    public function hasAvailableTickets(Event $event, int $ticketsSold): bool
    {
        return ($event->getMaxTickets() - $ticketsSold) > 0;
    }

    public function cancelEvent(Event $event): void
    {
        if (!$this->canBeCancelled($event)) {
            throw new \App\Exception\Event\EventCannotBeCancelledException(
                $event->getId()->toString(),
                'Event cannot be cancelled in current state: ' . $event->getStatus()->value
            );
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(\App\Enum\EventStatus::CANCELLED);
        $event->setCancelledAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function unpublishEvent(Event $event, int $ticketsSold = 0): void
    {
        if (!$this->canBeUnpublished($event, $ticketsSold)) {
            throw new \App\Exception\Event\EventCannotBeUnpublishedException(
                $event->getId()->toString(),
                $ticketsSold
            );
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(\App\Enum\EventStatus::DRAFT);
        $event->setPublishedAt(null);
        $this->entityManager->flush();
    }

    public function completeEvent(Event $event): void
    {
        if (!$this->canBeCompleted($event)) {
            throw new \App\Exception\Event\EventNotPublishableException(
                $event->getId()->toString(),
                'Event cannot be completed - must be published and past. Current status: ' . $event->getStatus()->value
            );
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(\App\Enum\EventStatus::COMPLETED);
        $this->entityManager->flush();
    }
}