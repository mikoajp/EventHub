<?php

namespace App\MessageHandler\Command\Payment;

use App\Entity\Ticket;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\Message\Command\Payment\RefundPaymentCommand;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class RefundPaymentHandler
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private TicketRepository $ticketRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function __invoke(RefundPaymentCommand $command): void
    {
        $this->logger->info('Processing refund', [
            'ticket_id' => $command->ticketId,
            'payment_id' => $command->paymentId,
            'amount' => $command->amount,
            'reason' => $command->reason
        ]);

        try {
            $ticket = $this->ticketRepository->find(Uuid::fromString($command->ticketId));

            if (!$ticket) {
                throw new \InvalidArgumentException('Ticket not found');
            }

            // Process refund through payment gateway
            $result = $this->paymentGateway->refundPayment($command->paymentId, $command->amount);

            if (!$result->success) {
                throw new \RuntimeException('Refund failed: ' . $result->message);
            }

            // Update ticket status
            $ticket->setStatus(Ticket::STATUS_REFUNDED);
            $this->entityManager->flush();

            $this->logger->info('Refund completed successfully', [
                'ticket_id' => $command->ticketId,
                'refund_id' => $result->paymentId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed', [
                'ticket_id' => $command->ticketId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
