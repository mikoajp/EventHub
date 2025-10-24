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
            ->setMaxAttendees($eventDTO->maxAttendees)
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
            ->setMaxAttendees($eventDTO->maxAttendees);

        $this->entityManager->flush();

        return $event;
    }

    public function canUserModifyEvent(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || 
               in_array('ROLE_ADMIN', $user->getRoles());
    }

    public function isEventPublishable(Event $event): bool
    {
        return $event->getStatus() === Event::STATUS_DRAFT &&
               $event->getName() &&
               $event->getEventDate() &&
               $event->getVenue();
    }

    public function canBeCancelled(Event $event): bool
    {
        return $event->canBeCancelled();
    }

    public function canBeUnpublished(Event $event): bool
    {
        return $event->canBeUnpublished();
    }

    public function cancelEvent(Event $event): void
    {
        $event->setStatus(Event::STATUS_CANCELLED);
        $event->setCancelledAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function unpublishEvent(Event $event): void
    {
        $event->setStatus(Event::STATUS_DRAFT);
        $event->setPublishedAt(null);
        $this->entityManager->flush();
    }
}