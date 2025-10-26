<?php

namespace App\MessageHandler\Command\Payment;

use App\Entity\Ticket;
use App\Message\Command\Payment\ProcessPaymentCommand;
use App\Message\Command\Payment\RefundPaymentCommand;
use App\Message\Event\PaymentProcessedEvent;
use App\Message\Event\TicketPurchasedEvent;
use App\Repository\TicketRepository;
use App\Service\IdempotencyService;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProcessPaymentHandler
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private PaymentService $paymentService,
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'messenger.bus.event')] private MessageBusInterface $eventBus,
        private IdempotencyService $idempotencyService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ProcessPaymentCommand $command): void
    {
        $idempotencyKey = $command->getIdempotencyKey();

        // Check idempotency
        try {
            $cachedResult = $this->idempotencyService->checkIdempotency(
                $idempotencyKey,
                ProcessPaymentCommand::class
            );

            if ($cachedResult !== null) {
                $this->logger->info('Payment already processed (idempotency)', [
                    'ticket_id' => $command->ticketId,
                    'idempotency_key' => $idempotencyKey
                ]);
                return;
            }
        } catch (\App\Exception\Idempotency\CommandAlreadyProcessingException $e) {
            $this->logger->warning('Payment is being processed concurrently', [
                'ticket_id' => $command->ticketId,
                'idempotency_key' => $idempotencyKey
            ]);
            throw $e;
        }

        $idempotencyRecord = $this->idempotencyService->startExecution(
            $idempotencyKey,
            ProcessPaymentCommand::class
        );

        $ticket = $this->ticketRepository->find(Uuid::fromString($command->ticketId));
        
        if (!$ticket) {
            $this->idempotencyService->markFailed($idempotencyRecord, 'Ticket not found');
            throw new \App\Exception\Ticket\TicketNotFoundException($command->ticketId);
        }

        // Idempotency check: if ticket already purchased, consider success
        if ($ticket->getStatus() === Ticket::STATUS_PURCHASED) {
            $this->logger->info('Ticket already purchased, skipping payment', [
                'ticket_id' => $command->ticketId
            ]);
            $this->idempotencyService->markCompleted($idempotencyRecord, ['status' => 'already_purchased']);
            return;
        }

        if ($ticket->getStatus() !== Ticket::STATUS_RESERVED) {
            $this->idempotencyService->markFailed($idempotencyRecord, 'Ticket is not in reserved status');
            throw new \App\Exception\Ticket\InvalidTicketStatusException(
                $command->ticketId,
                Ticket::STATUS_RESERVED,
                $ticket->getStatus()
            );
        }

        try {
            $paymentResult = $this->paymentService->processPayment(
                $command->paymentMethodId,
                $command->amount,
                $command->currency,
                [
                    'ticket_id' => $command->ticketId,
                    'event_id' => $ticket->getEvent()->getId()->toString(),
                    'user_id' => $ticket->getUser()->getId()->toString(),
                    'idempotency_key' => $idempotencyKey
                ]
            );

            if ($paymentResult->isSuccessful()) {
                $ticket->markAsPurchased();
                $this->entityManager->flush();
                
                $this->idempotencyService->markCompleted($idempotencyRecord, [
                    'status' => 'completed',
                    'payment_id' => $paymentResult->getPaymentId()
                ]);

                $this->eventBus->dispatch(new PaymentProcessedEvent(
                    $paymentResult->getPaymentId(),
                    $command->ticketId,
                    $command->amount,
                    'completed',
                    new \DateTimeImmutable()
                ));

                $this->eventBus->dispatch(new TicketPurchasedEvent(
                    $command->ticketId,
                    $ticket->getEvent()->getId()->toString(),
                    $ticket->getUser()->getId()->toString(),
                    $command->amount,
                    new \DateTimeImmutable()
                ));

                $this->logger->info('Payment processed successfully', [
                    'ticket_id' => $command->ticketId,
                    'payment_id' => $paymentResult->getPaymentId()
                ]);

            } else {
                // Payment failed - cancel ticket and mark as failed
                $ticket->setStatus(Ticket::STATUS_CANCELLED);
                $this->entityManager->flush();

                $this->idempotencyService->markFailed($idempotencyRecord, 'Payment failed: ' . $paymentResult->getMessage());

                $this->eventBus->dispatch(new PaymentProcessedEvent(
                    $paymentResult->getPaymentId() ?? 'none',
                    $command->ticketId,
                    $command->amount,
                    'failed',
                    new \DateTimeImmutable()
                ));

                $this->logger->warning('Payment failed', [
                    'ticket_id' => $command->ticketId,
                    'reason' => $paymentResult->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            // Rollback: cancel ticket and mark idempotency as failed
            $ticket->setStatus(Ticket::STATUS_CANCELLED);
            $this->entityManager->flush();

            $this->idempotencyService->markFailed($idempotencyRecord, $e->getMessage());

            $this->logger->error('Payment processing exception', [
                'ticket_id' => $command->ticketId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
