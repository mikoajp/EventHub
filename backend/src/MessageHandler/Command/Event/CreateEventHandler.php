<?php

namespace App\MessageHandler\Command\Event;

use App\Entity\Event;
use App\Entity\TicketType;
use App\Message\Command\Event\CreateEventCommand;
use App\Message\Event\EventCreatedEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class CreateEventHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MessageBusInterface $eventBus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(CreateEventCommand $command): string
    {
        $organizer = $this->userRepository->find(Uuid::fromString($command->organizerId));
        
        if (!$organizer) {
            throw new \InvalidArgumentException('Organizer not found');
        }

        $event = new Event();
        $event->setName($command->name)
              ->setDescription($command->description)
              ->setEventDate($command->eventDate)
              ->setVenue($command->venue)
              ->setMaxTickets($command->maxTickets)
              ->setOrganizer($organizer);

        // Create ticket types
        foreach ($command->ticketTypes as $ticketTypeData) {
            $ticketType = new TicketType();
            $ticketType->setName($ticketTypeData['name'])
                       ->setPrice($ticketTypeData['price'])
                       ->setQuantity($ticketTypeData['quantity'])
                       ->setEvent($event);
            
            $event->addTicketType($ticketType);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // Dispatch domain event
        $this->eventBus->dispatch(new EventCreatedEvent(
            $event->getId()->toString(),
            $organizer->getId()->toString(),
            new \DateTimeImmutable()
        ));

        return $event->getId()->toString();
    }
}