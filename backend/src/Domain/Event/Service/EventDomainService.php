<?php

namespace App\Domain\Event\Service;

use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EventDomainService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager
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
            ->setStatus(Event::STATUS_DRAFT);

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
               in_array('ROLE_ADMIN', $user->getRoles());
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
        return $event->getStatus() === Event::STATUS_DRAFT &&
               $event->getName() &&
               $event->getEventDate() &&
               $event->getVenue();
    }

    public function isPublished(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_PUBLISHED;
    }

    public function isDraft(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_DRAFT;
    }

    public function isCancelled(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_CANCELLED;
    }

    public function isCompleted(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_COMPLETED;
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
            throw new \DomainException('Event cannot be cancelled in current state');
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(Event::STATUS_CANCELLED);
        $event->setCancelledAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function unpublishEvent(Event $event, int $ticketsSold = 0): void
    {
        if (!$this->canBeUnpublished($event, $ticketsSold)) {
            throw new \DomainException('Event cannot be unpublished - tickets already sold');
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(Event::STATUS_DRAFT);
        $event->setPublishedAt(null);
        $this->entityManager->flush();
    }

    public function completeEvent(Event $event): void
    {
        if (!$this->canBeCompleted($event)) {
            throw new \DomainException('Event cannot be completed - must be published and past');
        }

        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(Event::STATUS_COMPLETED);
        $this->entityManager->flush();
    }
}