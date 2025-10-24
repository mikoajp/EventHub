<?php

namespace App\MessageHandler\Command\Ticket;

use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Entity\Ticket;
use App\Message\Command\Payment\ProcessPaymentCommand;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use App\Message\Event\TicketReservedEvent;
use App\Repository\EventRepository;
use App\Repository\TicketTypeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PurchaseTicketHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private TicketTypeRepository $ticketTypeRepository,
        private UserRepository $userRepository,
        private TicketAvailabilityChecker $availabilityChecker,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $eventBus
    ) {}

    public function __invoke(PurchaseTicketCommand $command): array
    {
        $this->entityManager->beginTransaction();
        
        try {
            $event = $this->eventRepository->find(Uuid::fromString($command->eventId));
            $ticketType = $this->ticketTypeRepository->find(Uuid::fromString($command->ticketTypeId));
            $user = $this->userRepository->find(Uuid::fromString($command->userId));

            if (!$event || !$ticketType || !$user) {
                throw new \InvalidArgumentException('Invalid event, ticket type, or user');
            }

            if (!$event->isPublished()) {
                throw new \InvalidArgumentException('Event is not published');
            }

            if (!$this->availabilityChecker->isAvailable($ticketType, $command->quantity)) {
                throw new \InvalidArgumentException('Not enough tickets available');
            }

            $tickets = [];
            $totalAmount = 0;

            for ($i = 0; $i < $command->quantity; $i++) {
                $ticket = new Ticket();
                $ticket->setEvent($event)
                       ->setTicketType($ticketType)
                       ->setUser($user)
                       ->setPrice($ticketType->getPrice())
                       ->setStatus(Ticket::STATUS_RESERVED);

                $this->entityManager->persist($ticket);
                $tickets[] = $ticket;
                $totalAmount += $ticketType->getPrice();
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $ticketIds = array_map(fn(Ticket $ticket) => $ticket->getId()->toString(), $tickets);

            foreach ($tickets as $ticket) {
                $this->commandBus->dispatch(new ProcessPaymentCommand(
                    $ticket->getId()->toString(),
                    $command->paymentMethodId,
                    $ticket->getPrice()
                ));

                $this->eventBus->dispatch(new TicketReservedEvent(
                    $ticket->getId()->toString(),
                    $event->getId()->toString(),
                    $user->getId()->toString(),
                    new \DateTimeImmutable()
                ));
            }

            return $ticketIds;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}