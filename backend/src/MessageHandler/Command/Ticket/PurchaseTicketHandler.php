<?php

namespace App\MessageHandler\Command\Ticket;

use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Entity\Ticket;
use App\Message\Command\Payment\ProcessPaymentCommand;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use App\Message\Event\TicketReservedEvent;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\IdempotencyService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PurchaseTicketHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private TicketAvailabilityChecker $availabilityChecker,
        #[Autowire(service: 'messenger.bus.command')] private MessageBusInterface $commandBus,
        #[Autowire(service: 'messenger.bus.event')] private MessageBusInterface $eventBus,
        private IdempotencyService $idempotencyService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(PurchaseTicketCommand $command): array
    {
        $idempotencyKey = $command->getIdempotencyKey();

        // Check if this command was already processed (idempotency)
        try {
            $cachedResult = $this->idempotencyService->checkIdempotency(
                $idempotencyKey,
                PurchaseTicketCommand::class
            );

            if ($cachedResult !== null) {
                $this->logger->info('Returning cached result for duplicate command', [
                    'idempotency_key' => $idempotencyKey
                ]);
                return $cachedResult;
            }
        } catch (\App\Exception\Idempotency\CommandAlreadyProcessingException $e) {
            // Command is already being processed concurrently
            throw new \App\Exception\Idempotency\DuplicateRequestException('Duplicate ticket purchase request detected. Please wait.');
        }

        // Start tracking this command execution
        $idempotencyRecord = $this->idempotencyService->startExecution(
            $idempotencyKey,
            PurchaseTicketCommand::class
        );

        $this->entityManager->beginTransaction();
        
        try {
            $event = $this->eventRepository->findByUuid($command->eventId);
            $user = $this->userRepository->find(Uuid::fromString($command->userId));

            if (!$event) {
                throw new \App\Exception\Event\EventNotFoundException($command->eventId);
            }
            
            if (!$user) {
                throw new \App\Exception\User\UserNotFoundException($command->userId);
            }

            if (!$event->isPublished()) {
                throw new \App\Exception\Event\EventNotPublishedException($event->getId()->toString());
            }

            // Use pessimistic locking to prevent race conditions
            $ticketType = $this->entityManager->find(
                \App\Entity\TicketType::class,
                Uuid::fromString($command->ticketTypeId),
                LockMode::PESSIMISTIC_WRITE
            );

            if (!$ticketType) {
                throw new \App\Exception\Ticket\TicketTypeNotFoundException($command->ticketTypeId);
            }

            // Check availability with lock held
            if (!$this->availabilityChecker->isAvailable($ticketType, $command->quantity)) {
                throw new \App\Exception\Ticket\TicketNotAvailableException(
                    $command->ticketTypeId,
                    $command->quantity,
                    $ticketType->getAvailableQuantity()
                );
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

            // Mark idempotency as completed before dispatching async commands
            $this->idempotencyService->markCompleted($idempotencyRecord, $ticketIds);

            // Dispatch payment and event messages
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

            $this->logger->info('Ticket purchase completed successfully', [
                'idempotency_key' => $idempotencyKey,
                'ticket_ids' => $ticketIds,
                'user_id' => $command->userId,
                'quantity' => $command->quantity
            ]);

            return $ticketIds;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            // Mark idempotency as failed
            $this->idempotencyService->markFailed($idempotencyRecord, $e->getMessage());
            
            $this->logger->error('Ticket purchase failed', [
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
                'user_id' => $command->userId
            ]);
            
            throw $e;
        }
    }
}