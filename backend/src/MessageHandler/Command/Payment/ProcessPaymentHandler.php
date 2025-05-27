<?php

namespace App\MessageHandler\Command\Payment;

use App\Entity\Ticket;
use App\Message\Command\Payment\ProcessPaymentCommand;
use App\Message\Event\PaymentProcessedEvent;
use App\Message\Event\TicketPurchasedEvent;
use App\Repository\TicketRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProcessPaymentHandler
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private PaymentService $paymentService,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus
    ) {}

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(ProcessPaymentCommand $command): void
    {
        $ticket = $this->ticketRepository->find(Uuid::fromString($command->ticketId));
        
        if (!$ticket) {
            throw new \InvalidArgumentException('Ticket not found');
        }

        if ($ticket->getStatus() !== Ticket::STATUS_RESERVED) {
            throw new \InvalidArgumentException('Ticket is not in reserved status');
        }

        try {
            $paymentResult = $this->paymentService->processPayment(
                $command->paymentMethodId,
                $command->amount,
                $command->currency,
                [
                    'ticket_id' => $command->ticketId,
                    'event_id' => $ticket->getEvent()->getId()->toString(),
                    'user_id' => $ticket->getUser()->getId()->toString()
                ]
            );

            if ($paymentResult->isSuccessful()) {
                $ticket->markAsPurchased();
                $this->entityManager->flush();
                
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
            } else {
                $ticket->setStatus(Ticket::STATUS_CANCELLED);
                $this->entityManager->flush();

                $this->eventBus->dispatch(new PaymentProcessedEvent(
                    $paymentResult->getPaymentId(),
                    $command->ticketId,
                    $command->amount,
                    'failed',
                    new \DateTimeImmutable()
                ));
            }

        } catch (\Exception $e) {
            $ticket->setStatus(Ticket::STATUS_CANCELLED);
            $this->entityManager->flush();
            throw $e;
        } catch (ExceptionInterface $e) {
        }
    }
}